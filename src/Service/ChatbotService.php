<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\Chatbot\ExternalAiClientInterface;
use App\Entity\Formation;
use App\Enum\FormationLevel;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use App\Repository\ReviewRepository;
use App\Service\Formation\FormationChatbotAnswer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Keyword-based NLP over live {@see Formation} rows, with optional LLM delegation
 * via {@see ExternalAiClientInterface}.
 */
final class ChatbotService implements ChatbotServiceInterface
{
    private const CACHE_TTL_SECONDS = 180;

    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly ReviewRepository $reviewRepository,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly ?ExternalAiClientInterface $externalAi = null,
    ) {
    }

    public function answer(string $message, array $context): FormationChatbotAnswer
    {
        $started = microtime(true);
        $normMsg = $this->normalize($message);
        $cacheKey = $this->buildCacheKey($normMsg, $context);

        if (null !== $this->externalAi && '' !== trim($message)) {
            $ai = $this->externalAi->complete($message, $context);
            if (null !== $ai && '' !== trim($ai)) {
                $this->logger->info('chatbot.external_ai', ['chars' => mb_strlen($ai)]);

                return new FormationChatbotAnswer(trim($ai), 'external_ai', [], false);
            }
        }

        if (preg_match('/^(hi|hello|bonjour|salut|hey|coucou)\b/ui', $normMsg)) {
            $this->logger->info('chatbot.shortcut', ['intent' => 'greeting']);

            return new FormationChatbotAnswer(
                'Bonjour ! Je peux vous aider à parcourir les formations : prix, durée, catégorie, avis, popularité ou recommandations. Posez une question concrète ou dites « aide » pour des exemples.',
                'greeting',
                [],
                false,
            );
        }

        try {
            $answer = $this->cache->get($cacheKey, function (ItemInterface $item) use ($normMsg, $context) {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);

                return $this->computeAnswer($normMsg, $context);
            });
        } catch (\Throwable $e) {
            $this->logger->error('chatbot.failure', [
                'exception' => $e,
                'message_preview' => mb_substr($message, 0, 120),
            ]);

            return new FormationChatbotAnswer(
                'Le conseiller catalogue est temporairement indisponible. Réessayez dans un instant ou utilisez les filtres du catalogue.',
                'error',
                [],
                false,
            );
        }

        $this->logger->info('chatbot.answer', [
            'intent' => $answer->intent,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'result_count' => count($answer->formations),
            'cache_key_hash' => substr(sha1($cacheKey), 0, 12),
            'fallback' => $answer->fallback,
        ]);

        return $answer;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function computeAnswer(string $normMsg, array $context): FormationChatbotAnswer
    {
        $q = $this->trimString($context['search_query'] ?? null);
        $cat = $this->trimString($context['active_category'] ?? null);
        $lvl = $this->trimString($context['filter_level'] ?? null);

        $formations = $this->formationRepository->findCatalogFiltered(
            '' !== $q ? $q : null,
            '' !== $cat ? $cat : null,
            '' !== $lvl ? $lvl : null,
        );
        $formations = array_values(array_filter(
            $formations,
            static fn (Formation $f) => 'ACTIVE' === ($f->getStatus() ?? 'ACTIVE'),
        ));

        $ids = array_values(array_filter(array_map(static fn (Formation $f) => $f->getId(), $formations)));
        $ratings = $this->reviewRepository->getAggregatesForFormationIds($ids);
        $enrollments = $this->enrollmentRepository->countEnrollmentsByFormationIds($ids);

        if ('' === $normMsg) {
            return $this->welcomeAnswer($formations, $ratings, $enrollments, $q, $cat, $lvl);
        }

        if (preg_match('/^(combien|how many|nombre)\b/u', $normMsg) && !preg_match('/\b(prix|co[uû]te|cost)\b/u', $normMsg)) {
            return new FormationChatbotAnswer(
                sprintf(
                    'D’après vos filtres actuels, %d formation(s) figure(nt) dans le catalogue actif.',
                    count($formations)
                ),
                'count',
                $this->highlights(array_slice($formations, 0, 5), $ratings, $enrollments),
                false,
            );
        }

        $nlp = $this->detectNlpHints($normMsg);

        if ($nlp['help']) {
            return new FormationChatbotAnswer(
                $this->helpText(count($formations)),
                'help',
                [],
                false,
            );
        }

        if ($nlp['availability']) {
            return new FormationChatbotAnswer(
                'Les formations listées sont disponibles à l’inscription. Ouvrez une fiche puis utilisez « S’inscrire » (compte requis). Les prix et la durée sont indiqués sur chaque carte et en détail.',
                'availability',
                $this->highlights(array_slice($formations, 0, 3), $ratings, $enrollments),
                false,
            );
        }

        $filtered = $formations;

        if (null !== $nlp['category']) {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (Formation $f) => $f->getCategory() === $nlp['category'],
            ));
        }

        if (null !== $nlp['level']) {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (Formation $f) => $f->getLevel() === $nlp['level'],
            ));
        }

        if (null !== $nlp['max_duration']) {
            $max = $nlp['max_duration'];
            $filtered = array_values(array_filter(
                $filtered,
                static fn (Formation $f) => null !== $f->getDuration() && $f->getDuration() <= $max,
            ));
        }

        if (null !== $nlp['min_duration']) {
            $min = $nlp['min_duration'];
            $filtered = array_values(array_filter(
                $filtered,
                static fn (Formation $f) => null !== $f->getDuration() && $f->getDuration() >= $min,
            ));
        }

        if ($nlp['free_only']) {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (Formation $f) => true === $f->isFree() || (null !== $f->getPrice() && $f->getPrice() <= 0.0),
            ));
        }

        if (null !== $nlp['text_search']) {
            $needle = mb_strtolower($nlp['text_search']);
            $filtered = array_values(array_filter(
                $filtered,
                static function (Formation $f) use ($needle) {
                    $hay = mb_strtolower($f->getTitle().' '.(string) $f->getDescription());

                    return str_contains($hay, $needle);
                },
            ));
        }

        if ($nlp['token_search'] && [] !== $nlp['tokens']) {
            $narrowed = $this->filterByTokens($filtered, $nlp['tokens']);
            if ([] !== $narrowed) {
                $filtered = $narrowed;
            } else {
                $fromAll = $this->filterByTokens($formations, $nlp['tokens']);
                $filtered = [] !== $fromAll ? $fromAll : [];
            }
        }

        $intent = $nlp['sort_intent'] ?? 'relevance';

        if ('best_rated' === $intent) {
            usort($filtered, function (Formation $a, Formation $b) use ($ratings) {
                $ida = $a->getId();
                $idb = $b->getId();
                $avgA = $ratings[$ida]['average'] ?? 0.0;
                $avgB = $ratings[$idb]['average'] ?? 0.0;
                $cA = $ratings[$ida]['total'] ?? 0;
                $cB = $ratings[$idb]['total'] ?? 0;
                if (0 !== ($avgB <=> $avgA)) {
                    return $avgB <=> $avgA;
                }

                return $cB <=> $cA;
            });
        } elseif ('popular' === $intent) {
            usort($filtered, function (Formation $a, Formation $b) use ($enrollments): int {
                $ea = $enrollments[(int) $a->getId()] ?? 0;
                $eb = $enrollments[(int) $b->getId()] ?? 0;
                if ($ea === $eb) {
                    return strcmp($a->getTitle(), $b->getTitle());
                }

                return $eb <=> $ea;
            });
        } elseif ('recommend' === $intent) {
            $counts = array_map(static fn (Formation $f): int => $enrollments[(int) $f->getId()] ?? 0, $filtered);
            $maxE = [] !== $counts ? max(1, ...$counts) : 1;
            usort($filtered, function (Formation $a, Formation $b) use ($ratings, $enrollments, $maxE): int {
                $ida = (int) $a->getId();
                $idb = (int) $b->getId();
                $ra = ($ratings[$ida]['average'] ?? 0.0) / 5.0;
                $rb = ($ratings[$idb]['average'] ?? 0.0) / 5.0;
                $ea = ($enrollments[$ida] ?? 0) / $maxE;
                $eb = ($enrollments[$idb] ?? 0) / $maxE;
                $sa = $ra * 0.65 + $ea * 0.35;
                $sb = $rb * 0.65 + $eb * 0.35;
                if (abs($sa - $sb) < 1e-9) {
                    return strcmp($a->getTitle(), $b->getTitle());
                }

                return $sb <=> $sa;
            });
        } elseif ('price_asc' === $intent) {
            usort($filtered, static function (Formation $a, Formation $b) {
                $pa = $a->isFree() || (null !== $a->getPrice() && $a->getPrice() <= 0.0) ? 0.0 : (float) ($a->getPrice() ?? PHP_FLOAT_MAX);
                $pb = $b->isFree() || (null !== $b->getPrice() && $b->getPrice() <= 0.0) ? 0.0 : (float) ($b->getPrice() ?? PHP_FLOAT_MAX);

                return $pa <=> $pb;
            });
        } elseif ('price_desc' === $intent) {
            usort($filtered, static function (Formation $a, Formation $b) {
                $pa = null === $a->getPrice() ? -1.0 : (float) $a->getPrice();
                $pb = null === $b->getPrice() ? -1.0 : (float) $b->getPrice();

                return $pb <=> $pa;
            });
        } elseif ('duration_asc' === $intent) {
            usort($filtered, static fn (Formation $a, Formation $b) => ($a->getDuration() ?? PHP_INT_MAX) <=> ($b->getDuration() ?? PHP_INT_MAX));
        } elseif ('duration_desc' === $intent) {
            usort($filtered, static fn (Formation $a, Formation $b) => ($b->getDuration() ?? 0) <=> ($a->getDuration() ?? 0));
        }

        $highlights = $this->highlights(array_slice($filtered, 0, 8), $ratings, $enrollments);

        if ([] === $filtered) {
            return new FormationChatbotAnswer(
                $this->fallbackNoMatch($normMsg, count($formations)),
                'fallback',
                [],
                true,
            );
        }

        $reply = $this->buildReplyText($intent, $nlp, $filtered, $ratings, $highlights);

        return new FormationChatbotAnswer($reply, $intent, $highlights, false);
    }

    /**
     * @param Formation[] $formations
     * @param array<int, array{average: float, total: int}> $ratings
     * @param array<int, int> $enrollments
     */
    private function welcomeAnswer(array $formations, array $ratings, array $enrollments, string $q, string $cat, string $lvl): FormationChatbotAnswer
    {
        $n = count($formations);
        if (0 === $n) {
            return new FormationChatbotAnswer(
                'Aucune formation ne correspond aux filtres actuels. Élargissez la catégorie ou effacez la recherche.',
                'empty_catalog',
                [],
                true,
            );
        }

        $parts = ['Je parcours le catalogue Skilora en temps réel ('.$n.' formation(s)).'];
        if ('' !== $q || '' !== $cat || '' !== $lvl) {
            $parts[] = 'Des filtres sont actifs sur la page — je les respecte dans mes suggestions.';
        }
        $parts[] = 'Vous pouvez demander par exemple : formations les mieux notées, les plus populaires, les moins chères, les plus courtes, ou « développement » / « data science ».';

        $top = $formations;
        usort($top, function (Formation $a, Formation $b) use ($ratings) {
            $ida = $a->getId();
            $idb = $b->getId();
            $avgA = $ratings[$ida]['average'] ?? 0.0;
            $avgB = $ratings[$idb]['average'] ?? 0.0;

            return $avgB <=> $avgA;
        });

        return new FormationChatbotAnswer(
            implode(' ', $parts),
            'welcome',
            $this->highlights(array_slice($top, 0, 5), $ratings, $enrollments),
            false,
        );
    }

    private function helpText(int $catalogSize): string
    {
        return 'Exemples de questions : « formations gratuites », « les mieux notées », « les plus populaires », « recommandation », « les plus courtes », « cours de développement », « data science », « niveau débutant », « combien de formations ? ». Catalogue actuel : '.$catalogSize.' formation(s) selon vos filtres.';
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildCacheKey(string $normMsg, array $context): string
    {
        $payload = [
            'm' => $normMsg,
            'q' => $this->trimString($context['search_query'] ?? null),
            'cat' => $this->trimString($context['active_category'] ?? null),
            'lvl' => $this->trimString($context['filter_level'] ?? null),
        ];

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

        return 'formation_chatbot.v2.'.hash('sha256', $encoded);
    }

    private function normalize(string $message): string
    {
        $t = trim($message);
        if ('' === $t) {
            return '';
        }

        return mb_strtolower($t);
    }

    private function trimString(mixed $v): string
    {
        if (!\is_string($v)) {
            return '';
        }

        return trim($v);
    }

    /**
     * @return array{
     *   sort_intent: string|null,
     *   category: string|null,
     *   level: FormationLevel|null,
     *   max_duration: int|null,
     *   min_duration: int|null,
     *   free_only: bool,
     *   text_search: string|null,
     *   token_search: bool,
     *   tokens: list<string>,
     *   help: bool,
     *   availability: bool
     * }
     */
    private function detectNlpHints(string $normMsg): array
    {
        $help = (bool) preg_match('/\b(aide|help|exemple|comment|what can you)\b/u', $normMsg);
        $availability = (bool) preg_match('/\b(disponib|inscript|enroll|register|places|seat)\b/u', $normMsg);

        $sortIntent = null;
        if (preg_match('/\b(populaire|popular|tendance|trending|most enrolled)\b/u', $normMsg)) {
            $sortIntent = 'popular';
        } elseif (preg_match('/\b(mieux not|meilleur|top rated|best rated|best review|étoiles|stars|avis|note)\b/u', $normMsg)) {
            $sortIntent = 'best_rated';
        } elseif (preg_match('/\b(gratuit|free|pas cher|bon marché|cheap|affordable|moins cher|économique)\b/u', $normMsg)) {
            $sortIntent = 'price_asc';
        } elseif (preg_match('/\b(cher|expensive|premium|pricey|plus cher)\b/u', $normMsg)) {
            $sortIntent = 'price_desc';
        } elseif (preg_match('/\b(court|rapide|short|quick|express|acceler|few hours|peu de temps)\b/u', $normMsg)) {
            $sortIntent = 'duration_asc';
        } elseif (preg_match('/\b(long|complet|extensive|approfondi|many hours)\b/u', $normMsg)) {
            $sortIntent = 'duration_desc';
        } elseif (preg_match('/\b(recommand|recommend|suggest|conseil|suggestion)\b/u', $normMsg)) {
            $sortIntent = 'recommend';
        }

        $maxDuration = null;
        if (preg_match('/\b(court|rapide|short|quick|express|few hours|peu de temps)\b/u', $normMsg)) {
            $maxDuration = 20;
        }

        $minDuration = null;
        if (preg_match('/\b(long|complet|extensive|approfondi)\b/u', $normMsg)) {
            $minDuration = 36;
        }

        $freeOnly = (bool) preg_match('/\b(gratuit|free|pas cher|cheap)\b/u', $normMsg);

        $category = null;
        if (preg_match('/\b(développement|developpement|development|dev|code|programming|symfony|php|javascript|backend|frontend)\b/u', $normMsg)) {
            $category = Formation::CATEGORY_DEVELOPMENT;
        } elseif (preg_match('/\b(data science|machine learning|données|donnees|python)\b/u', $normMsg)) {
            $category = Formation::CATEGORY_DATA_SCIENCE;
        } elseif (preg_match('/\b(design|ui|ux|figma|graphique)\b/u', $normMsg)) {
            $category = Formation::CATEGORY_DESIGN;
        } elseif (preg_match('/\b(langue|language|anglais|english|français|french)\b/u', $normMsg)) {
            $category = Formation::CATEGORY_LANGUAGES;
        } elseif (preg_match('/\b(business|gestion|mba|marketing)\b/u', $normMsg)) {
            $category = Formation::CATEGORY_BUSINESS;
        }

        $level = null;
        if (preg_match('/\b(débutant|debutant|beginner|entry)\b/u', $normMsg)) {
            $level = FormationLevel::BEGINNER;
        } elseif (preg_match('/\b(intermédiaire|intermediaire|intermediate)\b/u', $normMsg)) {
            $level = FormationLevel::INTERMEDIATE;
        } elseif (preg_match('/\b(avancé|avance|advanced|expert)\b/u', $normMsg)) {
            $level = FormationLevel::ADVANCED;
        }

        $tokens = $this->extractTokens($normMsg);
        $tokenSearch = !$help && !$availability && null === $category && null === $sortIntent && null === $level && null === $maxDuration && null === $minDuration && !$freeOnly;

        return [
            'sort_intent' => $sortIntent ?? 'relevance',
            'category' => $category,
            'level' => $level,
            'max_duration' => $maxDuration,
            'min_duration' => $minDuration,
            'free_only' => $freeOnly,
            'text_search' => null,
            'token_search' => $tokenSearch,
            'tokens' => $tokens,
            'help' => $help,
            'availability' => $availability,
        ];
    }

    /**
     * @return list<string>
     */
    private function extractTokens(string $normMsg): array
    {
        $raw = preg_split('/[^\p{L}\p{N}]+/u', $normMsg, -1, PREG_SPLIT_NO_EMPTY);
        if (!\is_array($raw)) {
            return [];
        }
        $stop = ['les', 'des', 'une', 'pour', 'avec', 'dans', 'sur', 'the', 'and', 'for', 'with', 'course', 'formation', 'formations', 'about'];
        $out = [];
        foreach ($raw as $w) {
            $w = mb_strtolower((string) $w);
            if (mb_strlen($w) < 3) {
                continue;
            }
            if (\in_array($w, $stop, true)) {
                continue;
            }
            $out[] = $w;
        }

        return array_slice(array_values(array_unique($out)), 0, 8);
    }

    /**
     * @param Formation[] $formations
     *
     * @return Formation[]
     */
    private function filterByTokens(array $formations, array $tokens): array
    {
        if ([] === $tokens) {
            return [];
        }

        return array_values(array_filter(
            $formations,
            static function (Formation $f) use ($tokens) {
                $hay = mb_strtolower($f->getTitle().' '.(string) $f->getDescription());
                foreach ($tokens as $t) {
                    if (str_contains($hay, $t)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    /**
     * @param Formation[] $filtered
     * @param array<int, array{average: float, total: int}> $ratings
     * @param list<array<string, mixed>> $highlights
     */
    private function buildReplyText(string $intent, array $nlp, array $filtered, array $ratings, array $highlights): string
    {
        $shown = count($highlights);
        $total = count($filtered);

        $intro = match ($intent) {
            'best_rated' => 'Voici les formations les mieux notées (selon les avis publiés)',
            'popular' => 'Voici les formations les plus suivies (inscriptions)',
            'recommend' => 'Voici des suggestions équilibrant avis et popularité',
            'price_asc' => 'Voici les formations les plus abordables en premier',
            'price_desc' => 'Voici les formations triées par prix décroissant',
            'duration_asc' => 'Voici les parcours les plus courts',
            'duration_desc' => 'Voici les parcours les plus longs / complets',
            default => 'Voici les formations qui correspondent le mieux',
        };

        $lines = [$intro.' ('.$total.' au total, '.$shown.' affichée(s) ci-dessous).'];

        if ('best_rated' === $intent) {
            $withReviews = array_values(array_filter(
                $filtered,
                static fn (Formation $f) => ($ratings[$f->getId()]['total'] ?? 0) > 0,
            ));
            if ([] === $withReviews && [] !== $filtered) {
                $lines[] = 'Peu d’avis sont encore publiés : les notes moyennes peuvent être à 0 — consultez les fiches pour plus de détails.';
            }
        }

        if (null !== $nlp['category']) {
            $lines[] = 'Filtre catégorie appliqué : '.(Formation::CATEGORY_LABELS_FR[$nlp['category']] ?? $nlp['category']).'.';
        }

        return implode("\n", $lines);
    }

    private function fallbackNoMatch(string $normMsg, int $catalogSize): string
    {
        return 'Je n’ai pas trouvé de formation correspondant à « '.mb_substr($normMsg, 0, 80).' » avec les filtres actuels. '
            .'Essayez d’élargir la recherche ou posez une question comme « mieux notées », « gratuites », « populaires », ou un mot-clé du titre. '
            .'Catalogue accessible : '.$catalogSize.' formation(s).';
    }

    /**
     * @param Formation[] $slice
     * @param array<int, array{average: float, total: int}> $ratings
     * @param array<int, int> $enrollmentCounts
     *
     * @return list<array<string, mixed>>
     */
    private function highlights(array $slice, array $ratings, array $enrollmentCounts = []): array
    {
        $out = [];
        foreach ($slice as $f) {
            $id = (int) $f->getId();
            $agg = $ratings[$id] ?? ['average' => 0.0, 'total' => 0];
            $out[] = [
                'id' => $id,
                'title' => $f->getTitle(),
                'categoryLabel' => $f->getCategoryLabelFr(),
                'levelLabel' => $f->getLevelLabelFr(),
                'durationHours' => $f->getDuration(),
                'priceLabel' => $f->getPriceDisplayFr(),
                'currency' => $f->getCurrency(),
                'isFree' => $f->isFree(),
                'averageRating' => $agg['average'],
                'reviewCount' => $agg['total'],
                'enrollmentCount' => $enrollmentCounts[$id] ?? 0,
                'url' => $this->urlGenerator->generate('app_formation_show', ['id' => $id]),
            ];
        }

        return $out;
    }
}

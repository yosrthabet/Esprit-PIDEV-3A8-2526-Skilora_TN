<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Formation\Entity\Formation;
use App\Formation\FormationLevel;
use App\Formation\FormationStatus;
use App\Formation\Repository\EnrollmentRepository;
use App\Formation\Repository\FormationRepository;
use App\Formation\Repository\FormationReviewRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ChatbotService
{
    private const CACHE_TTL_SECONDS = 180;

    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly FormationReviewRepository $reviewRepository,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function answer(string $message, array $context): FormationChatbotAnswer
    {
        $normMsg = mb_strtolower(trim($message));
        $cacheKey = 'formation_chatbot.v2.' . hash('sha256', json_encode(['m' => $normMsg, 'cat' => $context['category'] ?? '', 'lvl' => $context['level'] ?? ''], JSON_THROW_ON_ERROR));

        if (preg_match('/^(hi|hello|bonjour|salut|hey|coucou)\b/ui', $normMsg)) {
            return new FormationChatbotAnswer(
                'Bonjour ! Je peux vous aider à parcourir les formations : prix, durée, catégorie, avis, popularité ou recommandations.',
                'greeting',
            );
        }

        try {
            $answer = $this->cache->get($cacheKey, function (ItemInterface $item) use ($normMsg, $context) {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);
                return $this->compute($normMsg, $context);
            });
        } catch (\Throwable $e) {
            $this->logger->error('chatbot.failure', ['exception' => $e]);
            return new FormationChatbotAnswer('Le conseiller catalogue est temporairement indisponible.', 'error', [], false);
        }

        return $answer;
    }

    /** @param array<string, mixed> $context */
    private function compute(string $normMsg, array $context): FormationChatbotAnswer
    {
        $category = is_string($context['category'] ?? null) && $context['category'] !== '' ? $context['category'] : null;
        $levelStr = is_string($context['level'] ?? null) && $context['level'] !== '' ? $context['level'] : null;
        $level = $levelStr !== null ? FormationLevel::tryFrom($levelStr) : null;

        $formations = $this->formationRepository->findPublished($category, 100, null, $level);

        if ($normMsg === '') {
            return $this->welcome($formations);
        }

        if (preg_match('/\b(combien|how many|nombre)\b/u', $normMsg)) {
            return new FormationChatbotAnswer(
                sprintf('%d formation(s) dans le catalogue actuel.', count($formations)),
                'count',
                $this->highlights(array_slice($formations, 0, 5)),
            );
        }

        if (preg_match('/\b(aide|help|exemple|comment)\b/u', $normMsg)) {
            return new FormationChatbotAnswer(
                'Exemples : « formations gratuites », « les mieux notées », « les plus populaires », « courtes », « développement », « débutant ». Catalogue : ' . count($formations) . ' formation(s).',
                'help',
            );
        }

        $filtered = $this->applyNlpFilters($formations, $normMsg);
        $intent = $this->detectSortIntent($normMsg);
        $filtered = $this->sort($filtered, $intent);

        if ($filtered === []) {
            return new FormationChatbotAnswer(
                'Aucune formation ne correspond à « ' . mb_substr($normMsg, 0, 80) . ' ». Essayez un autre mot-clé.',
                'fallback',
                [],
                true,
            );
        }

        $highlights = $this->highlights(array_slice($filtered, 0, 8));
        $intro = match ($intent) {
            'best_rated' => 'Formations les mieux notées',
            'popular' => 'Formations les plus suivies',
            'price_asc' => 'Formations les plus abordables',
            'duration_asc' => 'Parcours les plus courts',
            default => 'Formations correspondantes',
        };

        return new FormationChatbotAnswer(
            $intro . ' (' . count($filtered) . ' au total, ' . count($highlights) . ' affichée(s)).',
            $intent,
            $highlights,
        );
    }

    /** @param Formation[] $formations */
    private function welcome(array $formations): FormationChatbotAnswer
    {
        if ($formations === []) {
            return new FormationChatbotAnswer('Aucune formation publiée.', 'empty_catalog', [], true);
        }

        return new FormationChatbotAnswer(
            'Je parcours le catalogue Skilora (' . count($formations) . ' formation(s)). Posez une question : mieux notées, populaires, gratuites, courtes…',
            'welcome',
            $this->highlights(array_slice($formations, 0, 5)),
        );
    }

    /** @return Formation[] */
    private function applyNlpFilters(array $formations, string $normMsg): array
    {
        $filtered = $formations;

        if (preg_match('/\b(gratuit|free|pas cher|cheap)\b/u', $normMsg)) {
            $filtered = array_values(array_filter($filtered, static fn (Formation $f) => $f->getPriceAmount() === null || (float) $f->getPriceAmount() <= 0));
        }

        $level = null;
        if (preg_match('/\b(débutant|debutant|beginner)\b/u', $normMsg)) {
            $level = FormationLevel::BEGINNER;
        } elseif (preg_match('/\b(intermédiaire|intermediaire|intermediate)\b/u', $normMsg)) {
            $level = FormationLevel::INTERMEDIATE;
        } elseif (preg_match('/\b(avancé|avance|advanced|expert)\b/u', $normMsg)) {
            $level = FormationLevel::ADVANCED;
        }
        if ($level !== null) {
            $filtered = array_values(array_filter($filtered, static fn (Formation $f) => $f->getLevel() === $level));
        }

        if (preg_match('/\b(court|rapide|short|quick)\b/u', $normMsg)) {
            $filtered = array_values(array_filter($filtered, static fn (Formation $f) => $f->getDurationHours() <= 20));
        }

        $tokens = $this->extractTokens($normMsg);
        if ($tokens !== []) {
            $narrowed = array_values(array_filter($filtered, static function (Formation $f) use ($tokens) {
                $hay = mb_strtolower($f->getTitle() . ' ' . ($f->getDescription() ?? ''));
                foreach ($tokens as $t) {
                    if (str_contains($hay, $t)) {
                        return true;
                    }
                }
                return false;
            }));
            if ($narrowed !== []) {
                $filtered = $narrowed;
            }
        }

        return $filtered;
    }

    private function detectSortIntent(string $normMsg): string
    {
        if (preg_match('/\b(populaire|popular|tendance)\b/u', $normMsg)) return 'popular';
        if (preg_match('/\b(mieux not|meilleur|top rated|best|avis|note)\b/u', $normMsg)) return 'best_rated';
        if (preg_match('/\b(gratuit|free|pas cher|cheap|moins cher)\b/u', $normMsg)) return 'price_asc';
        if (preg_match('/\b(court|rapide|short|quick)\b/u', $normMsg)) return 'duration_asc';
        if (preg_match('/\b(recommand|recommend|suggest|conseil)\b/u', $normMsg)) return 'recommend';
        return 'relevance';
    }

    /** @param Formation[] $formations */
    private function sort(array $formations, string $intent): array
    {
        if ($intent === 'duration_asc') {
            usort($formations, static fn (Formation $a, Formation $b) => $a->getDurationHours() <=> $b->getDurationHours());
        } elseif ($intent === 'price_asc') {
            usort($formations, static fn (Formation $a, Formation $b) => ((float) ($a->getPriceAmount() ?? 0)) <=> ((float) ($b->getPriceAmount() ?? 0)));
        }

        return $formations;
    }

    /**
     * @param Formation[] $slice
     * @return list<array<string, mixed>>
     */
    private function highlights(array $slice): array
    {
        $out = [];
        foreach ($slice as $f) {
            $id = $f->getId();
            if ($id === null) continue;
            $review = $this->reviewRepository->summarizeForFormation($f);
            $out[] = [
                'id' => $id,
                'title' => $f->getTitle(),
                'category' => $f->getCategory(),
                'level' => $f->getLevel()->value,
                'durationHours' => $f->getDurationHours(),
                'price' => $f->getPriceAmount(),
                'averageRating' => $review['average'],
                'reviewCount' => $review['count'],
                'url' => $this->urlGenerator->generate('app_formation_show', ['id' => $id]),
            ];
        }
        return $out;
    }

    /** @return list<string> */
    private function extractTokens(string $normMsg): array
    {
        $raw = preg_split('/[^\p{L}\p{N}]+/u', $normMsg, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($raw)) return [];
        $stop = ['les', 'des', 'une', 'pour', 'avec', 'dans', 'sur', 'the', 'and', 'for', 'with', 'course', 'formation', 'formations', 'about'];
        $out = [];
        foreach ($raw as $w) {
            $w = mb_strtolower((string) $w);
            if (mb_strlen($w) < 3 || in_array($w, $stop, true)) continue;
            $out[] = $w;
        }
        return array_slice(array_values(array_unique($out)), 0, 8);
    }
}

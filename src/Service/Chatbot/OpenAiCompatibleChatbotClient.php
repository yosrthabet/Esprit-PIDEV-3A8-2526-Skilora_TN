<?php

declare(strict_types=1);

namespace App\Service\Chatbot;

use App\Contract\Chatbot\ExternalAiClientInterface;
use App\Entity\Formation;
use App\Repository\FormationRepository;
use App\Repository\ReviewRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * OpenAI-compatible Chat Completions (works with Groq, OpenAI, Mistral, etc.).
 *
 * Free tier example: https://console.groq.com/keys — endpoint https://api.groq.com/openai/v1/chat/completions
 */
final class OpenAiCompatibleChatbotClient implements ExternalAiClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly FormationRepository $formationRepository,
        private readonly ReviewRepository $reviewRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    /**
     * @param array<string, mixed> $context Keys: search_query, active_category, filter_level
     */
    public function complete(string $userMessage, array $context): ?string
    {
        $trimmed = trim($userMessage);
        if ('' === $trimmed) {
            return null;
        }

        if ('' === $this->apiKey) {
            $this->logger->debug('chatbot.llm.skip', ['reason' => 'empty_api_key']);

            return null;
        }

        $q = $this->str($context['search_query'] ?? null);
        $cat = $this->str($context['active_category'] ?? null);
        $lvl = $this->str($context['filter_level'] ?? null);

        try {
            $formations = $this->formationRepository->findCatalogFiltered(
                '' !== $q ? $q : null,
                '' !== $cat ? $cat : null,
                '' !== $lvl ? $lvl : null,
            );
            $formations = array_values(array_filter(
                $formations,
                static fn (Formation $f) => 'ACTIVE' === ($f->getStatus() ?? 'ACTIVE'),
            ));
            $formations = array_slice($formations, 0, 45);

            $ids = array_values(array_filter(array_map(static fn (Formation $f) => $f->getId(), $formations)));
            $ratings = [] !== $ids ? $this->reviewRepository->getAggregatesForFormationIds($ids) : [];

            $catalogText = $this->buildCatalogText($formations, $ratings);
            $systemPrompt = <<<PROMPT
Tu es l'assistant catalogue de la plateforme e-learning Skilora. Tu réponds en français (tu peux mélanger quelques termes anglais si l'utilisateur écrit en anglais).
Base-toi UNIQUEMENT sur la liste de formations fournie ci-dessous (données réelles de la base). Si une information manque, dis-le honnêtement.
Pour chaque formation pertinente, cite le titre et au moins deux critères parmi : prix, durée, catégorie, niveau, note moyenne, nombre d'avis.
N'invente pas de formations ni de prix. Les URL sont relatives au site.
Si la liste est vide, explique qu'aucun résultat ne correspond aux filtres et suggère d'élargir la recherche.

LISTE DES FORMATIONS (filtres page déjà appliqués) :
{$catalogText}
PROMPT;

            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'temperature' => 0.35,
                    'max_tokens' => 900,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $trimmed],
                    ],
                ],
            ]);

            $status = $response->getStatusCode();
            $body = $response->getContent(false);

            if ($status < 200 || $status >= 300) {
                $this->logger->error('chatbot.llm.http_error', [
                    'status' => $status,
                    'body_preview' => mb_substr($body, 0, 500),
                ]);

                return null;
            }

            $data = json_decode($body, true);
            if (!\is_array($data)) {
                $this->logger->error('chatbot.llm.invalid_json', ['preview' => mb_substr($body, 0, 300)]);

                return null;
            }

            $content = $data['choices'][0]['message']['content'] ?? null;
            if (!\is_string($content) || '' === trim($content)) {
                $this->logger->warning('chatbot.llm.empty_content', ['response_keys' => array_keys($data)]);

                return null;
            }

            $this->logger->info('chatbot.llm.success', [
                'model' => $this->model,
                'catalog_size' => count($formations),
                'reply_chars' => mb_strlen($content),
            ]);

            return trim($content);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('chatbot.llm.transport', ['exception' => $e->getMessage()]);

            return null;
        } catch (\Throwable $e) {
            $this->logger->error('chatbot.llm.exception', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param Formation[] $formations
     * @param array<int, array{average: float, total: int}> $ratings
     */
    private function buildCatalogText(array $formations, array $ratings): string
    {
        if ([] === $formations) {
            return '(Aucune formation active ne correspond aux filtres actuels du catalogue.)';
        }

        $lines = [];
        foreach ($formations as $f) {
            $id = (int) $f->getId();
            $agg = $ratings[$id] ?? ['average' => 0.0, 'total' => 0];
            $url = $this->urlGenerator->generate('app_formation_show', ['id' => $id]);
            $desc = $f->getDescription();
            $descShort = null !== $desc ? mb_substr(preg_replace('/\s+/', ' ', strip_tags($desc)), 0, 160) : '';

            $dur = $f->getDuration();
            $durLabel = null === $dur ? '—' : (string) $dur;

            $lines[] = sprintf(
                '- [%d] %s | catégorie: %s | niveau: %s | durée: %s h | prix: %s | note moyenne: %s (%d avis) | url: %s | extrait: %s',
                $id,
                $f->getTitle(),
                $f->getCategoryLabelFr(),
                $f->getLevelLabelFr(),
                $durLabel,
                $f->getPriceDisplayFr(),
                $agg['average'] > 0 ? (string) $agg['average'] : 'n/a',
                $agg['total'],
                $url,
                $descShort,
            );
        }

        return implode("\n", $lines);
    }

    private function str(mixed $v): string
    {
        return \is_string($v) ? trim($v) : '';
    }
}

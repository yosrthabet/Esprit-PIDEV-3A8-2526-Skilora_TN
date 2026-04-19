<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de résumé automatique via Groq AI (Llama 3).
 * Génère des résumés de discussions communautaires avec retry et fallback algorithmique.
 * Porté depuis le module JavaFX community.
 */
class AISummaryService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';
    private const MAX_RETRIES = 3;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $groqApiKey,
    ) {
    }

    /**
     * Génère un résumé d'un texte ou d'une discussion.
     */
    public function summarize(string $text, string $language = 'fr'): string
    {
        if (trim($text) === '') {
            return '';
        }

        // Essayer l'API Groq avec retry
        if ($this->groqApiKey !== '') {
            $result = $this->summarizeWithGroq($text, $language);
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback: résumé algorithmique local
        return $this->algorithmicSummary($text);
    }

    /**
     * Résume plusieurs messages/posts d'une discussion.
     *
     * @param string[] $messages
     */
    public function summarizeDiscussion(array $messages, string $language = 'fr'): string
    {
        if (empty($messages)) {
            return '';
        }

        $combined = implode("\n---\n", $messages);
        $prompt = sprintf(
            "Résume la discussion suivante en %s en 2-3 phrases claires et concises:\n\n%s",
            $language === 'fr' ? 'français' : 'English',
            $combined
        );

        if ($this->groqApiKey !== '') {
            $result = $this->callGroqApi($prompt, $language);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->algorithmicSummary($combined);
    }

    private function summarizeWithGroq(string $text, string $language): ?string
    {
        $langLabel = $language === 'fr' ? 'français' : 'English';
        $prompt = sprintf(
            "Résume le texte suivant en %s en 2-3 phrases concises. Retourne uniquement le résumé:\n\n%s",
            $langLabel,
            $text
        );

        return $this->callGroqApi($prompt, $language);
    }

    private function callGroqApi(string $prompt, string $language): ?string
    {
        $backoff = 2;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => self::GROQ_MODEL,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a professional summarizer. Create concise, informative summaries. Respond in ' . ($language === 'fr' ? 'French' : 'English') . '.',
                            ],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'max_tokens' => 500,
                        'temperature' => 0.5,
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();
                $summary = $data['choices'][0]['message']['content'] ?? null;

                if ($summary !== null) {
                    $this->logger->info('AI summary generated successfully', ['attempt' => $attempt]);
                    return trim($summary);
                }
            } catch (\Throwable $e) {
                $this->logger->warning('AI summary attempt failed', ['attempt' => $attempt, 'error' => $e->getMessage()]);

                if ($attempt < self::MAX_RETRIES) {
                    // Pas de sleep en web, on retente immédiatement
                    $backoff *= 2;
                }
            }
        }

        return null;
    }

    /**
     * Résumé algorithmique local (fallback quand l'API est indisponible).
     * Extrait les phrases les plus significatives basé sur la longueur et les mots-clés.
     */
    private function algorithmicSummary(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($sentences)) {
            return mb_substr($text, 0, 200) . (mb_strlen($text) > 200 ? '…' : '');
        }

        // Score chaque phrase par longueur et mots-clés importants
        $keywords = ['important', 'essentiel', 'résultat', 'conclusion', 'objectif', 'principal',
                     'key', 'result', 'conclusion', 'goal', 'main', 'summary'];

        $scored = [];
        foreach ($sentences as $idx => $sentence) {
            $score = mb_strlen($sentence);
            foreach ($keywords as $kw) {
                if (mb_stripos($sentence, $kw) !== false) {
                    $score += 50;
                }
            }
            // Bonus pour la première et deuxième phrase
            if ($idx === 0) $score += 30;
            if ($idx === 1) $score += 15;

            $scored[] = ['sentence' => $sentence, 'score' => $score, 'idx' => $idx];
        }

        // Trier par score décroissant et prendre les 3 meilleures
        usort($scored, fn ($a, $b) => $b['score'] - $a['score']);
        $top = array_slice($scored, 0, 3);

        // Remettre dans l'ordre original
        usort($top, fn ($a, $b) => $a['idx'] - $b['idx']);

        return implode(' ', array_column($top, 'sentence'));
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TranslationService
{
    private const MYMEMORY_API_URL = 'https://api.mymemory.translated.net/get';

    private const LANGUAGE_CODES = [
        'fr' => 'French',
        'en' => 'English',
        'ar' => 'Arabic',
        'es' => 'Spanish',
        'de' => 'German',
    ];

    private const LOCAL_DICTIONARY = [
        'fr' => [
            'Bonjour' => ['en' => 'Hello', 'ar' => 'مرحبا'],
            'Merci' => ['en' => 'Thank you', 'ar' => 'شكرا'],
            'Communauté' => ['en' => 'Community', 'ar' => 'مجتمع'],
            'Publication' => ['en' => 'Post', 'ar' => 'منشور'],
            'Événement' => ['en' => 'Event', 'ar' => 'حدث'],
            'Groupe' => ['en' => 'Group', 'ar' => 'مجموعة'],
            'Message' => ['en' => 'Message', 'ar' => 'رسالة'],
            'Ami' => ['en' => 'Friend', 'ar' => 'صديق'],
            'Invitation' => ['en' => 'Invitation', 'ar' => 'دعوة'],
        ],
        'en' => [
            'Hello' => ['fr' => 'Bonjour', 'ar' => 'مرحبا'],
            'Thank you' => ['fr' => 'Merci', 'ar' => 'شكرا'],
            'Community' => ['fr' => 'Communauté', 'ar' => 'مجتمع'],
        ],
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $chatbotApiUrl,
        private readonly string $chatbotApiKey,
        private readonly string $chatbotModel,
    ) {
    }

    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        if ($sourceLang === $targetLang || trim($text) === '') {
            return $text;
        }

        $localResult = $this->translateLocal($text, $sourceLang, $targetLang);
        if ($localResult !== null) {
            return $localResult;
        }

        if ($this->chatbotApiKey !== '') {
            $llmResult = $this->translateWithLlm($text, $sourceLang, $targetLang);
            if ($llmResult !== null) {
                return $llmResult;
            }
        }

        $publicResult = $this->translatePublic($text, $targetLang);
        if ($publicResult !== $text) {
            return $publicResult;
        }

        return $text;
    }

    private function translateLocal(string $text, string $sourceLang, string $targetLang): ?string
    {
        return self::LOCAL_DICTIONARY[$sourceLang][$text][$targetLang] ?? null;
    }

    private function translateWithLlm(string $text, string $sourceLang, string $targetLang): ?string
    {
        $sourceLabel = self::LANGUAGE_CODES[$sourceLang] ?? $sourceLang;
        $targetLabel = self::LANGUAGE_CODES[$targetLang] ?? $targetLang;

        try {
            $response = $this->httpClient->request('POST', $this->chatbotApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->chatbotApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->chatbotModel,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a professional translator. Translate accurately and return only the translated text.'],
                        ['role' => 'user', 'content' => sprintf('Translate from %s to %s. Return ONLY the translated text: %s', $sourceLabel, $targetLabel, $text)],
                    ],
                    'max_tokens' => 2048,
                    'temperature' => 0.3,
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray();
            $translated = $data['choices'][0]['message']['content'] ?? null;

            if (\is_string($translated) && trim($translated) !== '') {
                return trim($translated);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('LLM translation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function translatePublic(string $text, string $targetLang = 'en'): string
    {
        if (mb_strlen($text) < 2) {
            return $text;
        }

        $mirrors = [
            'https://libretranslate.de/translate',
            'https://translate.argosopentech.com/translate',
            'https://translate.terraprint.co/translate',
        ];

        foreach ($mirrors as $mirror) {
            try {
                $response = $this->httpClient->request('POST', $mirror, [
                    'json' => ['q' => $text, 'source' => 'auto', 'target' => $targetLang, 'format' => 'text'],
                    'timeout' => 5,
                ]);
                $data = $response->toArray();
                $result = $data['translatedText'] ?? null;
                if (\is_string($result) && $result !== $text) {
                    return $result;
                }
            } catch (\Throwable) {
            }
        }

        try {
            $sourceLang = ($targetLang === 'fr') ? 'en' : 'fr';
            $response = $this->httpClient->request('GET', self::MYMEMORY_API_URL, [
                'query' => ['q' => $text, 'langpair' => $sourceLang . '|' . $targetLang],
                'timeout' => 5,
            ]);
            $data = $response->toArray();
            $translated = $data['responseData']['translatedText'] ?? null;
            if (\is_string($translated) && ($data['responseStatus'] ?? 0) === 200
                && !str_contains($translated, 'PLEASE SELECT TWO DISTINCT LANGUAGES')
                && !str_contains($translated, 'MYMEMORY WARNING')) {
                return $translated;
            }
        } catch (\Throwable) {
        }

        return $text;
    }

    /** @return string[] */
    public function getSupportedLanguages(): array
    {
        return array_keys(self::LANGUAGE_CODES);
    }

    public function detectLanguage(string $text): string
    {
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar';
        }
        if (preg_match('/[àâäéèêëïîôùûüÿçœæ]/i', $text)) {
            return 'fr';
        }

        return 'en';
    }
}

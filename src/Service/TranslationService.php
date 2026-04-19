<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de traduction multi-couches.
 * Stratégie: Dictionnaire local → Groq API (Llama 3) → MyMemory API → Texte original
 * Porté depuis le module JavaFX community.
 */
class TranslationService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';
    private const MYMEMORY_API_URL = 'https://api.mymemory.translated.net/get';

    private const LANGUAGE_CODES = [
        'fr' => 'French',
        'en' => 'English',
        'ar' => 'Arabic',
        'es' => 'Spanish',
        'de' => 'German',
    ];

    /** Dictionnaire local pour les traductions fréquentes */
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
        private readonly string $groqApiKey,
    ) {
    }

    /**
     * Traduit un texte de sourceLang vers targetLang.
     * Utilise une stratégie multi-couches avec fallback.
     */
    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        if ($sourceLang === $targetLang || trim($text) === '') {
            return $text;
        }

        // Couche 1: Dictionnaire local
        $localResult = $this->translateLocal($text, $sourceLang, $targetLang);
        if ($localResult !== null) {
            return $localResult;
        }

        // Couche 2: Groq API (Llama 3)
        if ($this->groqApiKey !== '') {
            $groqResult = $this->translateWithGroq($text, $sourceLang, $targetLang);
            if ($groqResult !== null) {
                return $groqResult;
            }
        }

        // Couche 3: MyMemory API (gratuit, pas de clé)
        $myMemoryResult = $this->translateWithMyMemory($text, $sourceLang, $targetLang);
        if ($myMemoryResult !== null) {
            return $myMemoryResult;
        }

        // Fallback: retourner le texte original
        return $text;
    }

    private function translateLocal(string $text, string $sourceLang, string $targetLang): ?string
    {
        $dict = self::LOCAL_DICTIONARY[$sourceLang] ?? null;
        if ($dict === null) {
            return null;
        }

        $translation = $dict[$text][$targetLang] ?? null;
        if ($translation !== null) {
            $this->logger->debug('Translation found in local dictionary', ['text' => $text]);
        }

        return $translation;
    }

    private function translateWithGroq(string $text, string $sourceLang, string $targetLang): ?string
    {
        $sourceLabel = self::LANGUAGE_CODES[$sourceLang] ?? $sourceLang;
        $targetLabel = self::LANGUAGE_CODES[$targetLang] ?? $targetLang;

        $prompt = sprintf(
            'Translate the following text from %s to %s. Return ONLY the translated text, nothing else: %s',
            $sourceLabel,
            $targetLabel,
            $text
        );

        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::GROQ_MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a professional translator. Translate accurately and return only the translated text.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 2048,
                    'temperature' => 0.3,
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray();
            $translated = $data['choices'][0]['message']['content'] ?? null;

            if ($translated !== null) {
                $this->logger->info('Groq translation successful', ['source' => $sourceLang, 'target' => $targetLang]);
                return trim($translated);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Groq translation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function translateWithMyMemory(string $text, string $sourceLang, string $targetLang): ?string
    {
        try {
            $response = $this->httpClient->request('GET', self::MYMEMORY_API_URL, [
                'query' => [
                    'q' => $text,
                    'langpair' => $sourceLang . '|' . $targetLang,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            $translated = $data['responseData']['translatedText'] ?? null;

            if ($translated !== null && ($data['responseStatus'] ?? 0) === 200) {
                $this->logger->info('MyMemory translation successful');
                return trim($translated);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('MyMemory translation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /** @return string[] */
    public function getSupportedLanguages(): array
    {
        return array_keys(self::LANGUAGE_CODES);
    }

    public function detectLanguage(string $text): string
    {
        // Détection simple basée sur les caractères
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar';
        }
        if (preg_match('/[àâäéèêëïîôùûüÿçœæ]/i', $text)) {
            return 'fr';
        }
        return 'en';
    }
}

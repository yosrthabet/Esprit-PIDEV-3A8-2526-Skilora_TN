<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PublicTranslationService
{
    private const LIBRE_MIRRORS = [
        'https://libretranslate.de/translate',
        'https://translate.argosopentech.com/translate',
        'https://translate.terraprint.co/translate',
    ];

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function translate(string $text, string $targetLang = 'en'): ?string
    {
        if (strlen($text) < 2) return $text;

        // Strategy 1: Try LibreTranslate mirrors
        foreach (self::LIBRE_MIRRORS as $mirror) {
            $result = $this->tryLibreTranslate($mirror, $text, $targetLang);
            if ($result && $result !== $text) {
                return $result;
            }
        }

        // Strategy 2: Fallback to MyMemory with autodetect
        $result = $this->tryMyMemory($text, $targetLang);
        if ($result && $result !== $text) {
            return $result;
        }

        return $text;
    }

    private function tryLibreTranslate(string $url, string $text, string $targetLang): ?string
    {
        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'q' => $text,
                    'source' => 'auto',
                    'target' => $targetLang,
                    'format' => 'text',
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();
            return $data['translatedText'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function tryMyMemory(string $text, string $targetLang): ?string
    {
        try {
            // Smart source language: if target is French, assume English source, otherwise assume French
            $sourceLang = ($targetLang === 'fr') ? 'en' : 'fr';

            $response = $this->httpClient->request('GET', 'https://api.mymemory.translated.net/get', [
                'query' => [
                    'q' => $text,
                    'langpair' => $sourceLang . '|' . $targetLang,
                    'de' => 'admin@skilora.com',
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();
            $translated = $data['responseData']['translatedText'] ?? null;

            if (!$translated ||
                str_contains($translated, 'PLEASE SELECT TWO DISTINCT LANGUAGES') ||
                str_contains($translated, 'MYMEMORY WARNING')
            ) {
                return null;
            }

            return $translated;
        } catch (\Exception $e) {
            return null;
        }
    }
}

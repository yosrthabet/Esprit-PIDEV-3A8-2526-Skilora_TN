<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * OCR API extractor for image CVs (JPG/PNG/WebP).
 */
final class OcrApiTextExtractor
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $ocrApiUrl = '',
        private readonly string $ocrApiKey = '',
        private readonly int $timeoutSeconds = 20,
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->ocrApiUrl) !== '' && trim($this->ocrApiKey) !== '';
    }

    public function extractFromImagePath(string $absolutePath): ?string
    {
        if (!$this->isConfigured() || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', $this->ocrApiUrl, [
                'timeout' => $this->timeoutSeconds,
                'headers' => [
                    'apikey' => $this->ocrApiKey,
                ],
                'body' => [
                    // OCR.Space compatible fields
                    'language' => 'fre',
                    'isOverlayRequired' => 'false',
                    'file' => fopen($absolutePath, 'rb'),
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return null;
            }

            $json = $response->toArray(false);
            if (!\is_array($json)) {
                return null;
            }

            $parts = $json['ParsedResults'] ?? null;
            if (!\is_array($parts)) {
                $this->logger->warning('OCR API response missing ParsedResults', ['response' => $json]);

                return null;
            }

            $chunks = [];
            foreach ($parts as $part) {
                if (!\is_array($part)) {
                    continue;
                }
                $txt = $part['ParsedText'] ?? null;
                if (\is_string($txt) && trim($txt) !== '') {
                    $chunks[] = trim($txt);
                }
            }

            $text = trim(implode("\n", $chunks));

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            $this->logger->warning('OCR API extraction failed', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}


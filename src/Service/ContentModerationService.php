<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Appelle le microservice Python de modération (NudeNet + OpenAI).
 */
class ContentModerationService
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
        // URL du microservice Python (configurable via .env)
        $this->baseUrl = rtrim($_ENV['MODERATION_SERVICE_URL'] ?? 'http://127.0.0.1:5000', '/');
    }

    /**
     * Modère le texte. Retourne ['safe' => bool, 'reason' => ?string].
     */
    public function moderateText(string $text): array
    {
        if (empty(trim($text))) {
            return ['safe' => true, 'reason' => null];
        }

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/moderate/text', [
                'json' => ['text' => $text],
                'timeout' => 15,
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Text moderation failed: ' . $e->getMessage());
            // En cas d'erreur du service, on laisse passer (fail-open)
            return ['safe' => true, 'reason' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Modère une image par chemin local (envoi du fichier). Retourne ['safe' => bool, 'reason' => ?string].
     */
    public function moderateImage(string $filePath): array
    {
        if (empty(trim($filePath)) || !file_exists($filePath)) {
            return ['safe' => true, 'reason' => null];
        }

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/moderate/image', [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'body' => [
                    'file' => fopen($filePath, 'r'),
                ],
                'timeout' => 30,
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Image moderation failed: ' . $e->getMessage());
            return ['safe' => true, 'reason' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Modère le contenu complet d'un post (texte + fichier image local).
     * Retourne ['safe' => bool, 'reasons' => string[]].
     */
    public function moderatePost(string $text, ?string $imageUrl = null, ?string $imageFilePath = null): array
    {
        $reasons = [];

        // 1) Modération du texte
        $textResult = $this->moderateText($text);
        if (!($textResult['safe'] ?? true)) {
            $reasons[] = $textResult['reason'] ?? 'Texte inapproprié détecté';
        }

        // 2) Modération de l'image (fichier local)
        if ($imageFilePath) {
            $imageResult = $this->moderateImage($imageFilePath);
            if (!($imageResult['safe'] ?? true)) {
                $reasons[] = $imageResult['reason'] ?? 'Image inappropriée détectée';
            }
        }

        return [
            'safe' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Vérifie si le service de modération est disponible.
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/health', [
                'timeout' => 5,
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }
}

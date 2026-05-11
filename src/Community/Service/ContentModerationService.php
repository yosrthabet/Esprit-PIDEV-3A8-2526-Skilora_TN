<?php

declare(strict_types=1);

namespace App\Community\Service;

use App\Service\AI\SkiloraMlClient;
use Psr\Log\LoggerInterface;

class ContentModerationService
{
    public function __construct(
        private readonly SkiloraMlClient $mlClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{safe: bool, reason: string|null, score: float}
     */
    public function moderate(string $content): array
    {
        $result = $this->mlClient->moderateContent(['content' => $content]);

        return [
            'safe' => (bool) ($result['safe'] ?? true),
            'reason' => is_string($result['reason'] ?? null) ? $result['reason'] : null,
            'score' => is_numeric($result['score'] ?? null) ? (float) $result['score'] : 1.0,
        ];
    }

    /**
     * @return array{safe: bool, reason: string|null}
     */
    public function moderateImage(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['safe' => true, 'reason' => null];
        }

        try {
            $imageData = file_get_contents($filePath);
            if ($imageData === false) {
                return ['safe' => true, 'reason' => null];
            }

            $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
            $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

            $result = $this->mlClient->moderateImage(['image_base64' => $base64]);

            $safe = (bool) ($result['safe'] ?? true);
            $reason = is_string($result['reason'] ?? null) ? $result['reason'] : null;

            if (!$safe) {
                $this->logger->info('Image flagged by moderation', ['reason' => $reason, 'file' => basename($filePath)]);
            }

            return ['safe' => $safe, 'reason' => $reason];
        } catch (\Throwable $e) {
            $this->logger->warning('Image moderation failed', ['error' => $e->getMessage()]);
            return ['safe' => true, 'reason' => null];
        }
    }
}

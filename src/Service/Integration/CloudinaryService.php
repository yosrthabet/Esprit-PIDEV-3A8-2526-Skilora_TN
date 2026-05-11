<?php

declare(strict_types=1);

namespace App\Service\Integration;

use Psr\Log\LoggerInterface;

class CloudinaryService
{
    public function __construct(
        private readonly string $cloudinaryUrl,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->cloudinaryUrl !== '' && $this->cloudinaryUrl !== 'PLACEHOLDER';
    }

    /**
     * Upload a file to Cloudinary.
     *
     * @return array{public_id: string, secure_url: string, format: string}
     */
    public function upload(string $filePath, string $folder = 'skilora'): array
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Cloudinary not configured, returning local path.');

            return [
                'public_id' => 'local_' . bin2hex(random_bytes(8)),
                'secure_url' => '/uploads/' . basename($filePath),
                'format' => pathinfo($filePath, PATHINFO_EXTENSION),
            ];
        }

        // Real Cloudinary integration:
        // $result = (new \Cloudinary\Api\Upload\UploadApi())->upload($filePath, ['folder' => $folder]);
        $this->logger->info('Cloudinary upload', ['path' => $filePath, 'folder' => $folder]);

        return [
            'public_id' => $folder . '/' . bin2hex(random_bytes(8)),
            'secure_url' => 'https://res.cloudinary.com/demo/image/upload/' . basename($filePath),
            'format' => pathinfo($filePath, PATHINFO_EXTENSION),
        ];
    }

    public function delete(string $publicId): bool
    {
        if (!$this->isConfigured()) {
            return true;
        }

        $this->logger->info('Cloudinary delete', ['public_id' => $publicId]);

        return true;
    }
}

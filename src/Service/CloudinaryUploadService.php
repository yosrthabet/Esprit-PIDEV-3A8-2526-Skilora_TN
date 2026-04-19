<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'upload média vers Cloudinary avec fallback local.
 * Supporte images, vidéos et fichiers audio.
 * Porté depuis le module JavaFX community.
 *
 * Configuration via .env:
 *   CLOUDINARY_CLOUD_NAME=skilora
 *   CLOUDINARY_UPLOAD_PRESET=skilora_unsigned
 */
class CloudinaryUploadService
{
    private const CLOUDINARY_BASE_URL = 'https://api.cloudinary.com/v1_1/';

    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;  // 10 MB
    private const MAX_VIDEO_SIZE = 50 * 1024 * 1024;  // 50 MB
    private const MAX_AUDIO_SIZE = 25 * 1024 * 1024;  // 25 MB

    private const ALLOWED_IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    private const ALLOWED_VIDEOS = ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm'];
    private const ALLOWED_AUDIO = ['wav', 'mp3', 'ogg', 'm4a', 'aac', 'wma'];

    private const FOLDERS = [
        'image' => 'skilora/community',
        'video' => 'skilora/community/videos',
        'audio' => 'skilora/community/vocal',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $cloudinaryCloudName,
        private readonly string $cloudinaryUploadPreset,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Upload un fichier (image, vidéo ou audio).
     * Retourne l'URL publique (Cloudinary ou locale).
     */
    public function upload(UploadedFile $file, string $type = 'image'): ?string
    {
        if (!$this->validateFile($file, $type)) {
            return null;
        }

        // Essayer Cloudinary d'abord
        if ($this->cloudinaryCloudName !== '' && $this->cloudinaryUploadPreset !== '') {
            $url = $this->uploadToCloudinary($file, $type);
            if ($url !== null) {
                return $url;
            }
        }

        // Fallback: stockage local
        return $this->uploadLocal($file, $type);
    }

    /**
     * Détecte automatiquement le type de fichier.
     */
    public function detectType(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, self::ALLOWED_IMAGES, true)) {
            return 'image';
        }
        if (in_array($ext, self::ALLOWED_VIDEOS, true)) {
            return 'video';
        }
        if (in_array($ext, self::ALLOWED_AUDIO, true)) {
            return 'audio';
        }

        return 'image'; // default
    }

    private function validateFile(UploadedFile $file, string $type): bool
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize();

        $allowed = match ($type) {
            'image' => self::ALLOWED_IMAGES,
            'video' => self::ALLOWED_VIDEOS,
            'audio' => self::ALLOWED_AUDIO,
            default => self::ALLOWED_IMAGES,
        };

        $maxSize = match ($type) {
            'image' => self::MAX_IMAGE_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            'audio' => self::MAX_AUDIO_SIZE,
            default => self::MAX_IMAGE_SIZE,
        };

        if (!in_array($ext, $allowed, true)) {
            $this->logger->warning('Invalid file type', ['ext' => $ext, 'type' => $type]);
            return false;
        }

        if ($size > $maxSize) {
            $this->logger->warning('File too large', ['size' => $size, 'max' => $maxSize]);
            return false;
        }

        return true;
    }

    private function uploadToCloudinary(UploadedFile $file, string $type): ?string
    {
        $resourceType = $type === 'image' ? 'image' : 'video'; // audio uses video endpoint
        $endpoint = self::CLOUDINARY_BASE_URL . $this->cloudinaryCloudName . '/' . $resourceType . '/upload';
        $folder = self::FOLDERS[$type] ?? self::FOLDERS['image'];

        try {
            $formData = [
                [
                    'name' => 'file',
                    'contents' => fopen($file->getPathname(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ],
                [
                    'name' => 'upload_preset',
                    'contents' => $this->cloudinaryUploadPreset,
                ],
                [
                    'name' => 'folder',
                    'contents' => $folder,
                ],
            ];

            $response = $this->httpClient->request('POST', $endpoint, [
                'body' => [
                    'upload_preset' => $this->cloudinaryUploadPreset,
                    'folder' => $folder,
                ],
                'extra' => [
                    'files' => [
                        'file' => $file->getPathname(),
                    ],
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray();
            $secureUrl = $data['secure_url'] ?? null;

            if ($secureUrl) {
                $this->logger->info('Cloudinary upload successful', ['url' => $secureUrl, 'type' => $type]);
                return $secureUrl;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Cloudinary upload failed, falling back to local', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function uploadLocal(UploadedFile $file, string $type): ?string
    {
        $uploadDir = $this->projectDir . '/public/uploads/community/' . $type;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();

        try {
            $file->move($uploadDir, $filename);
            $url = '/uploads/community/' . $type . '/' . $filename;
            $this->logger->info('Local upload successful', ['path' => $url]);
            return $url;
        } catch (\Throwable $e) {
            $this->logger->error('Local upload failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** @return string[] */
    public function getAllowedExtensions(string $type): array
    {
        return match ($type) {
            'image' => self::ALLOWED_IMAGES,
            'video' => self::ALLOWED_VIDEOS,
            'audio' => self::ALLOWED_AUDIO,
            default => [],
        };
    }

    public function getMaxSize(string $type): int
    {
        return match ($type) {
            'image' => self::MAX_IMAGE_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            'audio' => self::MAX_AUDIO_SIZE,
            default => self::MAX_IMAGE_SIZE,
        };
    }
}

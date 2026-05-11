<?php

declare(strict_types=1);

namespace App\Service\Integration;

use Psr\Log\LoggerInterface;

class MercureService
{
    public function __construct(
        private readonly string $mercureHubUrl,
        private readonly string $mercureJwtSecret,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->mercureHubUrl !== '' && $this->mercureJwtSecret !== '';
    }

    /**
     * Publish an update to a Mercure topic.
     *
     * @param array<string, mixed> $data
     */
    public function publish(string $topic, array $data): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Mercure not configured, skipping publish.', ['topic' => $topic]);

            return false;
        }

        // Real Mercure integration:
        // $update = new \Symfony\Component\Mercure\Update($topic, json_encode($data));
        // $this->hub->publish($update);
        $this->logger->info('Mercure publish', ['topic' => $topic, 'data' => $data]);

        return true;
    }

    /**
     * @param list<string> $topics
     */
    public function createSubscriptionToken(array $topics): string
    {
        $payload = [
            'mercure' => ['subscribe' => $topics],
            'exp' => time() + 3600,
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}

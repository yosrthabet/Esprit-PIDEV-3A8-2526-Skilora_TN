<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Psr\Log\LoggerInterface;

final class AnetiService
{
    private const FEED_FILE = 'data/job_feed.json';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{updated: string, count: int, jobs: list<array<string, mixed>>}
     */
    public function getLatestFeed(?string $sourceFilter = null, ?string $search = null, int $limit = 40): array
    {
        $path = $this->projectDir . '/' . self::FEED_FILE;
        if (!is_file($path) || !is_readable($path)) {
            $this->logger->info('ANETI feed file not found', ['path' => $path]);

            return ['updated' => '', 'count' => 0, 'jobs' => []];
        }

        try {
            $raw = file_get_contents($path);
            if ($raw === false) {
                return ['updated' => '', 'count' => 0, 'jobs' => []];
            }

            $data = json_decode($raw, true);
            if (!\is_array($data)) {
                return ['updated' => '', 'count' => 0, 'jobs' => []];
            }

            $jobs = $data['jobs'] ?? [];
            if (!\is_array($jobs)) {
                $jobs = [];
            }

            if ($sourceFilter !== null && $sourceFilter !== '') {
                $sf = mb_strtolower($sourceFilter);
                $jobs = array_values(array_filter($jobs, static function (array $job) use ($sf): bool {
                    return mb_strtolower((string) ($job['source'] ?? '')) === $sf;
                }));
            }

            if ($search !== null && trim($search) !== '') {
                $needle = mb_strtolower(trim($search));
                $jobs = array_values(array_filter($jobs, static function (array $job) use ($needle): bool {
                    $haystack = mb_strtolower(
                        ($job['title'] ?? '') . ' ' . ($job['description'] ?? '') . ' ' . ($job['location'] ?? '')
                    );

                    return str_contains($haystack, $needle);
                }));
            }

            $jobs = \array_slice($jobs, 0, $limit);

            return [
                'updated' => (string) ($data['updated'] ?? ''),
                'count' => \count($jobs),
                'jobs' => $jobs,
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to read ANETI feed', ['error' => $e->getMessage()]);

            return ['updated' => '', 'count' => 0, 'jobs' => []];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAnetiJobs(?string $search = null, int $limit = 20): array
    {
        $feed = $this->getLatestFeed('ANETI', $search, $limit);

        return $feed['jobs'];
    }

    public function getLastUpdated(): string
    {
        $feed = $this->getLatestFeed(limit: 0);

        return $feed['updated'];
    }
}

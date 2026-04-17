<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Source externe ANETI (API JSON ou RSS) vers un format homogène pour l'UI.
 */
final class AnetiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $anetiApiUrl = '',
        private readonly string $anetiRssUrl = '',
        private readonly int $timeoutSeconds = 12,
    ) {
    }

    /**
     * @return list<array{
     *   title: string,
     *   companyName: string,
     *   location: ?string,
     *   description: ?string,
     *   postedDate: ?\DateTimeImmutable,
     *   source: string,
     *   externalUrl: ?string
     * }>
     */
    public function fetchOffers(?string $search = null, int $limit = 20): array
    {
        $rows = [];

        if (trim($this->anetiApiUrl) !== '') {
            $rows = $this->fetchFromJsonApi(trim($this->anetiApiUrl), $search, $limit);
        }

        if ($rows === [] && trim($this->anetiRssUrl) !== '') {
            $rows = $this->fetchFromRss(trim($this->anetiRssUrl), $search, $limit);
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchFromJsonApi(string $url, ?string $search, int $limit): array
    {
        try {
            $query = ['limit' => max(1, $limit)];
            if ($search !== null && trim($search) !== '') {
                $query['q'] = trim($search);
            }

            $resp = $this->httpClient->request('GET', $url, [
                'query' => $query,
                'timeout' => $this->timeoutSeconds,
            ]);
            if ($resp->getStatusCode() < 200 || $resp->getStatusCode() >= 300) {
                return [];
            }
            $json = $resp->toArray(false);
            if (!\is_array($json)) {
                return [];
            }

            $items = $json['data'] ?? $json['offers'] ?? $json['items'] ?? $json;
            if (!\is_array($items)) {
                return [];
            }

            $mapped = [];
            foreach ($items as $it) {
                if (!\is_array($it)) {
                    continue;
                }
                $title = $this->pickString($it, ['title', 'job_title', 'position', 'name']);
                if ($title === null) {
                    continue;
                }
                $mapped[] = [
                    'title' => $title,
                    'companyName' => $this->pickString($it, ['company', 'company_name', 'employer']) ?? 'ANETI',
                    'location' => $this->pickString($it, ['location', 'city', 'region']),
                    'description' => $this->pickString($it, ['description', 'summary', 'content']),
                    'postedDate' => $this->parseDate($this->pickString($it, ['date', 'published_at', 'created_at', 'pubDate'])),
                    'source' => 'ANETI',
                    'externalUrl' => $this->pickString($it, ['url', 'link', 'apply_url']),
                ];
                if (\count($mapped) >= $limit) {
                    break;
                }
            }

            return $mapped;
        } catch (\Throwable $e) {
            $this->logger->warning('ANETI JSON fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchFromRss(string $url, ?string $search, int $limit): array
    {
        try {
            $resp = $this->httpClient->request('GET', $url, ['timeout' => $this->timeoutSeconds]);
            if ($resp->getStatusCode() < 200 || $resp->getStatusCode() >= 300) {
                return [];
            }
            $xmlText = $resp->getContent(false);
            if ($xmlText === '') {
                return [];
            }
            $xml = @simplexml_load_string($xmlText);
            if (!$xml instanceof \SimpleXMLElement) {
                return [];
            }

            $items = $xml->channel?->item ?? $xml->item ?? [];
            $mapped = [];
            $needle = $search !== null ? mb_strtolower(trim($search)) : '';
            foreach ($items as $item) {
                $title = trim((string) ($item->title ?? ''));
                if ($title === '') {
                    continue;
                }
                $desc = trim(strip_tags((string) ($item->description ?? '')));
                $company = $this->extractCompanyFromTitle($title);
                $location = $this->extractLocation($desc);
                if ($needle !== '' && !str_contains(mb_strtolower($title.' '.$desc), $needle)) {
                    continue;
                }

                $mapped[] = [
                    'title' => $title,
                    'companyName' => $company ?? 'ANETI',
                    'location' => $location,
                    'description' => $desc !== '' ? $desc : null,
                    'postedDate' => $this->parseDate(trim((string) ($item->pubDate ?? ''))),
                    'source' => 'ANETI',
                    'externalUrl' => trim((string) ($item->link ?? '')) ?: null,
                ];
                if (\count($mapped) >= $limit) {
                    break;
                }
            }

            return $mapped;
        } catch (\Throwable $e) {
            $this->logger->warning('ANETI RSS fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $keys
     */
    private function pickString(array $row, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $row)) {
                continue;
            }
            $v = $row[$k];
            if (\is_string($v) && trim($v) !== '') {
                return trim($v);
            }
        }

        return null;
    }

    private function parseDate(?string $raw): ?\DateTimeImmutable
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable(trim($raw));
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractCompanyFromTitle(string $title): ?string
    {
        foreach ([' - ', ' | ', ' @ '] as $sep) {
            if (!str_contains($title, $sep)) {
                continue;
            }
            $parts = explode($sep, $title, 2);
            if (isset($parts[1]) && trim($parts[1]) !== '') {
                return trim($parts[1]);
            }
        }

        return null;
    }

    private function extractLocation(string $text): ?string
    {
        if (preg_match('/\b(Tunis|Sfax|Sousse|Nabeul|Monastir|Gabes|Bizerte|Ariana|Marsa)\b/i', $text, $m)) {
            return $m[1];
        }

        return null;
    }
}


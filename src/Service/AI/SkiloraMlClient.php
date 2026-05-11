<?php

declare(strict_types=1);

namespace App\Service\AI;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SkiloraMlClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $skiloraMlBaseUrl,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->skiloraMlBaseUrl !== '' && $this->skiloraMlBaseUrl !== 'PLACEHOLDER';
    }

    // ── Recruitment ──

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function matchSkills(array $data): array { return $this->post('/recruitment/match', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function semanticMatch(array $data): array { return $this->post('/recruitment/semantic-match', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function analyzeCv(array $data): array { return $this->post('/recruitment/analyze-cv', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function cvJobFit(array $data): array { return $this->post('/recruitment/cv-job-fit', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function predictSalary(array $data): array { return $this->post('/recruitment/salary-predict', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function generateInterviewQuestions(array $data): array { return $this->post('/recruitment/interview-questions', $data); }

    // ── Formation ──

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function recommendFormations(array $data): array { return $this->post('/formation/recommend', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function predictCompletion(array $data): array { return $this->post('/formation/completion-predict', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function reviewFormation(array $data): array { return $this->post('/formation/review', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function personalizedRecommendations(array $data): array { return $this->post('/formation/personalized-recommend', $data); }

    // ── Community ──

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function moderateContent(array $data): array { return $this->post('/community/moderate', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function moderateImage(array $data): array { return $this->post('/community/moderate-image', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function analyzeSentiment(array $data): array { return $this->post('/community/sentiment', $data); }

    // ── Finance ──

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function detectAnomalies(array $data): array { return $this->post('/finance/anomaly-detect', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function analyzeSpending(array $data): array { return $this->post('/finance/spending-analysis', $data); }

    // ── Support ──

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function triageTicket(array $data): array { return $this->post('/support/triage', $data); }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function smartReply(array $data): array { return $this->post('/support/smart-reply', $data); }

    // ── Translation ──

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function translate(array $data): array { return $this->post('/community/translate', $data); }

    // ── Internal ──

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function post(string $path, array $data): array
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('ML API not configured, returning empty result.', ['path' => $path]);

            return ['error' => 'ML service not configured', 'mock' => true];
        }

        try {
            $sanitized = json_decode(json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}', true) ?? [];
            $response = $this->httpClient->request('POST', $this->skiloraMlBaseUrl . $path, [
                'json' => $sanitized,
                'timeout' => 30,
            ]);

            /** @var array<string, mixed> $result */
            $result = $response->toArray();

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('ML API call failed', ['path' => $path, 'error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }
}

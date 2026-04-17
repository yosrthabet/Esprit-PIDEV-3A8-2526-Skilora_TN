<?php

namespace App\Service\Finance;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FinancePdfAiSummaryService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly string $provider = 'local',
        private readonly string $apiKey = '',
        private readonly string $model = 'gpt-4o-mini',
        private readonly int $timeoutSeconds = 20,
    ) {
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return array{source: string, text: string}
     */
    public function buildSummary(array $report): array
    {
        try {
            $local = $this->buildLocalSummary($report);
            $provider = strtolower(trim($this->provider));

            if ($provider !== 'openai' || trim($this->apiKey) === '') {
                return ['source' => 'local', 'text' => $local];
            }

            try {
                $aiText = $this->generateWithOpenAi($report);
                if ($aiText === '') {
                    return ['source' => 'local', 'text' => $local];
                }

                return ['source' => 'ai', 'text' => $aiText];
            } catch (\Throwable $e) {
                $this->logger?->warning('Finance AI summary fallback to local.', [
                    'message' => $e->getMessage(),
                ]);

                return ['source' => 'local', 'text' => $local];
            }
        } catch (\Throwable $e) {
            $this->logger?->error('Finance summary failed completely.', [
                'message' => $e->getMessage(),
            ]);

            return [
                'source' => 'local',
                'text' => 'Resume indisponible: donnees finance partiellement lisibles. Consultez les sections detaillees du rapport.',
            ];
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function generateWithOpenAi(array $report): string
    {
        $facts = $this->extractFacts($report);
        $prompt = sprintf(
            "Tu es un analyste financier RH. Genere un resume en francais (max 120 mots), clair et professionnel, sans markdown.\nContexte:\n%s\nMets en avant: anciennete, niveau de paie, evolution, points de vigilance et recommandation courte.",
            json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'timeout' => max(5, $this->timeoutSeconds),
            'headers' => [
                'Authorization' => 'Bearer '.trim($this->apiKey),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu produis des resumes financiers RH concis et actionnables.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        /** @var array<string, mixed> $data */
        $data = $response->toArray(false);
        $text = (string) (($data['choices'][0]['message']['content'] ?? '') ?: '');

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return array<string, mixed>
     */
    private function extractFacts(array $report): array
    {
        $employee = is_array($report['employee'] ?? null) ? $report['employee'] : [];
        $metrics = is_array($report['metrics'] ?? null) ? $report['metrics'] : [];
        $trend = is_array($report['monthly_trend'] ?? null) ? $report['monthly_trend'] : [];

        $contracts = is_array($report['contracts'] ?? null) ? $report['contracts'] : [];
        $trendCount = \count($trend);
        $firstTrend = ($trendCount > 0 && \is_array($trend[0])) ? $trend[0] : [];
        $lastTrend = ($trendCount > 0 && \is_array($trend[$trendCount - 1])) ? $trend[$trendCount - 1] : [];
        $latestContract = (\count($contracts) > 0 && \is_array($contracts[0])) ? $contracts[0] : [];

        return [
            'employee' => [
                'name' => $employee['full_name'] ?? null,
                'id' => $employee['id'] ?? null,
                'tenure_label' => $latestContract['tenure_label'] ?? null,
            ],
            'metrics' => [
                'contracts_count' => $metrics['contracts_count'] ?? 0,
                'payslips_count' => $metrics['payslips_count'] ?? 0,
                'bonuses_count' => $metrics['bonuses_count'] ?? 0,
                'bonus_total' => $metrics['bonus_total'] ?? 0,
                'bank_accounts_count' => $metrics['bank_accounts_count'] ?? 0,
                'latest_estimated_net' => $metrics['latest_estimated_net'] ?? 0,
            ],
            'trend' => [
                'from_period' => $firstTrend['period'] ?? null,
                'from_net' => $firstTrend['net'] ?? null,
                'to_period' => $lastTrend['period'] ?? null,
                'to_net' => $lastTrend['net'] ?? null,
            ],
            'base_summary' => (string) ($report['summary'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function buildLocalSummary(array $report): string
    {
        $emp = is_array($report['employee'] ?? null) ? $report['employee'] : [];
        $employeeName = (string) ($emp['full_name'] ?? 'Employe');
        $metrics = is_array($report['metrics'] ?? null) ? $report['metrics'] : [];
        $trend = is_array($report['monthly_trend'] ?? null) ? $report['monthly_trend'] : [];
        $contractsData = is_array($report['contracts'] ?? null) ? $report['contracts'] : [];

        $contracts = (int) ($metrics['contracts_count'] ?? 0);
        $payslips = (int) ($metrics['payslips_count'] ?? 0);
        $bonuses = (int) ($metrics['bonuses_count'] ?? 0);
        $bonusTotal = (float) ($metrics['bonus_total'] ?? 0.0);
        $latestNet = (float) ($metrics['latest_estimated_net'] ?? 0.0);
        $accounts = (int) ($metrics['bank_accounts_count'] ?? 0);
        $latestContract = (\count($contractsData) > 0 && \is_array($contractsData[0])) ? $contractsData[0] : [];
        $tenureLabel = trim((string) ($latestContract['tenure_label'] ?? ''));
        $tenureSentence = $tenureLabel !== ''
            ? sprintf('Anciennete estimee: %s.', $tenureLabel)
            : 'Anciennete non renseignee.';

        $trendSentence = 'Tendance salariale insuffisante pour une lecture d evolution.';
        if (count($trend) >= 2) {
            $first = (float) ($trend[0]['net'] ?? 0.0);
            $last = (float) ($trend[count($trend) - 1]['net'] ?? 0.0);
            $delta = $last - $first;
            $pct = $first > 0.0 ? ($delta / $first) * 100.0 : 0.0;
            if ($delta > 0.01) {
                $trendSentence = sprintf('Le net estime progresse de %.2f%% sur la periode analysee.', $pct);
            } elseif ($delta < -0.01) {
                $trendSentence = sprintf('Le net estime recule de %.2f%% sur la periode analysee.', abs($pct));
            } else {
                $trendSentence = 'Le net estime reste globalement stable sur la periode analysee.';
            }
        }

        $riskSentence = $accounts === 0
            ? 'Point de vigilance: aucun compte bancaire actif n est rattache a ce dossier.'
            : 'La couverture bancaire est disponible pour les operations de paie.';

        return sprintf(
            "%s presente %d contrat(s), %d bulletin(s) et %d prime(s) pour un total de %s TND de bonus. Le dernier net estime est de %s TND. %s %s %s Recommandation: maintenir le suivi mensuel et verifier les anomalies avant cloture paie.",
            $employeeName,
            $contracts,
            $payslips,
            $bonuses,
            number_format($bonusTotal, 2, '.', ' '),
            number_format($latestNet, 2, '.', ' '),
            $tenureSentence,
            $trendSentence,
            $riskSentence
        );
    }
}

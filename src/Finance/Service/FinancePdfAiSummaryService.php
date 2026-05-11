<?php

declare(strict_types=1);

namespace App\Finance\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FinancePdfAiSummaryService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $chatbotApiUrl,
        private readonly string $chatbotApiKey,
        private readonly string $chatbotModel,
    ) {
    }

    /** @return array{source: string, text: string} */
    public function buildSummary(array $report): array
    {
        try {
            $local = $this->buildLocalSummary($report);

            if ($this->chatbotApiKey === '' || $this->chatbotApiUrl === '') {
                return ['source' => 'local', 'text' => $local];
            }

            $facts = json_encode($this->compactFacts($report), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $prompt = "Tu es un expert paie/RH. À partir des données JSON ci-dessous, rédige un résumé professionnel en français (60-100 mots) pour un rapport PDF employé.\n"
                . "Mentionne : salaire brut/net, cotisations, tendance si visible. Ne fabrique aucun chiffre.\n\n" . $facts;

            $response = $this->httpClient->request('POST', $this->chatbotApiUrl, [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->chatbotApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->chatbotModel ?: 'llama-3.3-70b-versatile',
                    'temperature' => 0.2,
                    'max_tokens' => 300,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu rédiges des synthèses paie concises et professionnelles.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

            return $text !== '' ? ['source' => 'ai', 'text' => $text] : ['source' => 'local', 'text' => $local];
        } catch (\Throwable $e) {
            $this->logger->warning('Finance PDF AI summary fallback.', ['error' => $e->getMessage()]);

            return ['source' => 'local', 'text' => $this->buildLocalSummary($report)];
        }
    }

    /** @return array<string, mixed> */
    private function compactFacts(array $report): array
    {
        return [
            'employee' => $report['employee_name'] ?? null,
            'period' => $report['period'] ?? null,
            'gross' => $report['total_gross'] ?? null,
            'net' => $report['total_net'] ?? null,
            'cnss_employee' => $report['cnss_employee'] ?? null,
            'cnss_employer' => $report['cnss_employer'] ?? null,
            'irpp' => $report['irpp'] ?? null,
            'payslip_count' => $report['payslip_count'] ?? null,
        ];
    }

    private function buildLocalSummary(array $report): string
    {
        $name = (string) ($report['employee_name'] ?? 'Employé');
        $period = (string) ($report['period'] ?? '—');
        $gross = $report['total_gross'] ?? 0;
        $net = $report['total_net'] ?? 0;
        $count = (int) ($report['payslip_count'] ?? 0);

        if ($count === 0) {
            return sprintf('Aucun bulletin de paie disponible pour %s sur la période %s.', $name, $period);
        }

        return sprintf(
            'Rapport pour %s — période %s. %d bulletin(s) traité(s). Brut cumulé : %.2f TND, net cumulé : %.2f TND. Ce document est généré automatiquement et ne constitue pas un document comptable officiel.',
            $name,
            $period,
            $count,
            (float) $gross,
            (float) $net,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Finance\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FinanceForecastAiCommentService
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
    public function buildComment(array $payload): array
    {
        try {
            $local = $this->buildLocalComment($payload);

            if ($this->chatbotApiKey === '' || $this->chatbotApiUrl === '') {
                return ['source' => 'local', 'text' => $local];
            }

            $facts = $this->compactFacts($payload);
            $prompt = "Tu es un contrôleur de gestion RH. À partir des faits JSON ci-dessous (déjà calculés), rédige un commentaire en français (80-120 mots).\n"
                . "Insiste sur : tendance, limites de la méthode (régression linéaire), impact des scénarios (% et montant fixe), et prudence (estimation indicative).\n"
                . "Ne rajoute AUCUN chiffre absent du JSON.\n\n"
                . json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $response = $this->httpClient->request('POST', $this->chatbotApiUrl, [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->chatbotApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->chatbotModel ?: 'llama-3.3-70b-versatile',
                    'temperature' => 0.25,
                    'max_tokens' => 400,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu commentes des prévisions paie de façon sobre et professionnelle.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

            return $text !== '' ? ['source' => 'ai', 'text' => $text] : ['source' => 'local', 'text' => $local];
        } catch (\Throwable $e) {
            $this->logger->warning('Finance forecast AI comment fallback.', ['error' => $e->getMessage()]);

            return ['source' => 'local', 'text' => $this->buildLocalComment($payload)];
        }
    }

    /** @return array<string, mixed> */
    private function compactFacts(array $payload): array
    {
        if (!empty($payload['empty'])) {
            return ['empty' => true];
        }

        $hist = is_array($payload['historical'] ?? null) ? $payload['historical'] : [];
        $lastH = $hist !== [] ? $hist[count($hist) - 1] : [];
        $firstH = $hist !== [] ? $hist[0] : [];
        $fc = is_array($payload['forecast'] ?? null) ? $payload['forecast'] : [];
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        return [
            'method' => $meta['method'] ?? null,
            'history_months_used' => $meta['history_months_used'] ?? null,
            'forecast_months' => $meta['forecast_months'] ?? null,
            'scenario_percent' => $meta['scenario_percent'] ?? null,
            'first_history_net' => $firstH['total_net'] ?? null,
            'last_history_net' => $lastH['total_net'] ?? null,
            'last_period' => $lastH['period'] ?? null,
            'forecast_preview' => array_slice($fc, 0, 3),
        ];
    }

    private function buildLocalComment(array $payload): string
    {
        if (!empty($payload['empty'])) {
            return 'Aucun bulletin : importez des données de paie pour activer la prévision.';
        }

        $meta = $payload['meta'] ?? [];
        $method = (string) ($meta['method'] ?? 'regression');
        $hUsed = (int) ($meta['history_months_used'] ?? 0);
        $fM = (int) ($meta['forecast_months'] ?? 0);
        $pct = (float) ($meta['scenario_percent'] ?? 0.0);
        $fix = (float) ($meta['scenario_extra_net_monthly'] ?? 0.0);

        $scenario = [];
        if (abs($pct) > 1e-6) {
            $scenario[] = sprintf('scénario global %+.2f %% sur la masse nette prévue', $pct);
        }
        if (abs($fix) > 1e-6) {
            $scenario[] = sprintf('+%.2f TND / mois ajoutés à chaque mois prévu', $fix);
        }
        $scenarioText = $scenario !== [] ? implode(' ; ', $scenario) : 'aucun scénario (projection brute)';

        return sprintf(
            'Prévision indicative basée sur une %s sur les %d dernier(s) mois disponibles, étendue sur %d mois futurs. Scénarios appliqués : %s. Cette estimation ne remplace pas la comptabilité.',
            $method === 'linear_regression' ? 'tendance linéaire (moindres carrés)' : 'projection',
            $hUsed,
            $fM,
            $scenarioText,
        );
    }
}

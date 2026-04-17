<?php

namespace App\Service\Finance;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Commentaire pédagogique sur la prévision (IA distante ou texte local).
 * Les montants affichés restent ceux calculés en PHP ; le modèle ne doit pas inventer de chiffres.
 */
final class FinanceForecastAiCommentService
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
     * @param array<string, mixed> $payload
     *
     * @return array{source: string, text: string}
     */
    public function buildComment(array $payload): array
    {
        try {
            $local = $this->buildLocalComment($payload);
            $provider = strtolower(trim($this->provider));

            if ($provider !== 'openai' || trim($this->apiKey) === '') {
                return ['source' => 'local', 'text' => $local];
            }

            try {
                $facts = $this->compactFacts($payload);
                $prompt = "Tu es un controleur de gestion RH. A partir des faits JSON ci-dessous (deja calcules), redige un commentaire en francais (80-120 mots).\n"
                    ."Insiste sur: tendance, limites de la methode (regression lineaire), impact des scenarios (% et montant fixe), et prudence (estimation indicative).\n"
                    ."Ne rajoute AUCUN chiffre absent du JSON.\n\n"
                    .json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                    'timeout' => max(5, $this->timeoutSeconds),
                    'headers' => [
                        'Authorization' => 'Bearer '.trim($this->apiKey),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $this->model,
                        'temperature' => 0.25,
                        'messages' => [
                            ['role' => 'system', 'content' => 'Tu commentes des previsions paie de facon sobre et professionnelle.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ],
                ]);

                /** @var array<string, mixed> $data */
                $data = $response->toArray(false);
                $text = trim((string) (($data['choices'][0]['message']['content'] ?? '') ?: ''));
                if ($text === '') {
                    return ['source' => 'local', 'text' => $local];
                }

                return ['source' => 'ai', 'text' => preg_replace('/\s+/', ' ', $text) ?? $text];
            } catch (\Throwable $e) {
                $this->logger?->warning('Finance forecast AI comment fallback.', ['message' => $e->getMessage()]);

                return ['source' => 'local', 'text' => $local];
            }
        } catch (\Throwable $e) {
            $this->logger?->error('Finance forecast comment failed.', ['message' => $e->getMessage()]);

            return [
                'source' => 'local',
                'text' => 'Commentaire indisponible. Les valeurs du tableau restent calculees localement.',
            ];
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function compactFacts(array $payload): array
    {
        if (!empty($payload['empty'])) {
            return ['empty' => true];
        }

        $hist = $payload['historical'] ?? [];
        $lastH = \is_array($hist) && $hist !== [] ? $hist[\count($hist) - 1] : [];
        $firstH = \is_array($hist) && $hist !== [] ? $hist[0] : [];

        $fc = $payload['forecast'] ?? [];
        $meta = \is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        return [
            'method' => $meta['method'] ?? null,
            'history_months_used' => $meta['history_months_used'] ?? null,
            'forecast_months' => $meta['forecast_months'] ?? null,
            'scenario_percent' => $meta['scenario_percent'] ?? null,
            'scenario_extra_net_monthly' => $meta['scenario_extra_net_monthly'] ?? null,
            'first_history_net' => $firstH['total_net'] ?? null,
            'last_history_net' => $lastH['total_net'] ?? null,
            'last_period' => $lastH['period'] ?? null,
            'forecast_preview' => \array_slice(\is_array($fc) ? $fc : [], 0, 3),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildLocalComment(array $payload): string
    {
        if (!empty($payload['empty'])) {
            return 'Aucun bulletin : importez des donnees de paie pour activer la prevision.';
        }

        $meta = $payload['meta'] ?? [];
        $method = (string) ($meta['method'] ?? 'regression');
        $hUsed = (int) ($meta['history_months_used'] ?? 0);
        $fM = (int) ($meta['forecast_months'] ?? 0);
        $pct = (float) ($meta['scenario_percent'] ?? 0.0);
        $fix = (float) ($meta['scenario_extra_net_monthly'] ?? 0.0);

        $scenario = [];
        if (abs($pct) > 1e-6) {
            $scenario[] = sprintf('scenario global %+s %% sur la masse nette prevue', rtrim(rtrim(sprintf('%.2f', $pct), '0'), '.'));
        }
        if (abs($fix) > 1e-6) {
            $scenario[] = sprintf('+%s TND / mois ajoutes a chaque mois prevu', number_format($fix, 2, '.', ' '));
        }
        $scenarioText = $scenario !== [] ? implode(' ; ', $scenario) : 'aucun scenario (projection brute)';

        return sprintf(
            'Prevision indicative basee sur une %s sur les %d dernier(s) mois disponibles, etendue sur %d mois futurs. '
            .'Scenarios appliques : %s. '
            .'Cette estimation ne remplace pas la comptabilite et doit etre validee par la direction ; les ecarts peuvent venir d embauches, primes ou arrets non modelises ici.',
            $method === 'linear_regression' ? 'tendance lineaire (moindres carres)' : 'projection',
            $hUsed,
            $fM,
            $scenarioText
        );
    }
}

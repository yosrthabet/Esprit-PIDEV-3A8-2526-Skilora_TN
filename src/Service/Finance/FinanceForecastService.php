<?php

namespace App\Service\Finance;

use App\Entity\Finance\Payslip;
use App\Repository\Finance\PayslipRepository;

/**
 * Prévision indicative de la masse salariale (somme des nets estimés par mois calendaire).
 * Calcul en PHP (régression linéaire) ; commentaire enrichi via FinanceForecastAiCommentService si configuré.
 */
final class FinanceForecastService
{
    public function __construct(
        private readonly PayslipRepository $payslipRepository,
        private readonly FinanceForecastAiCommentService $aiCommentService,
    ) {
    }

    /**
     * @return array{
     *   historical: list<array{period: string, total_net: float, total_gross: float, payslip_count: int}>,
     *   forecast: list<array{period: string, total_net: float, band_low: float, band_high: float}>,
     *   meta: array<string, mixed>,
     *   ai: array{source: string, text: string}
     * }
     */
    public function buildForecast(
        int $historyMonthsMax = 18,
        int $forecastMonths = 3,
        float $scenarioPercent = 0.0,
        float $scenarioExtraNetMonthly = 0.0,
    ): array {
        $forecastMonths = max(1, min(12, $forecastMonths));
        $historyMonthsMax = max(3, min(36, $historyMonthsMax));

        /** @var Payslip[] $payslips */
        $payslips = $this->payslipRepository->findAllOrdered();
        $monthly = $this->aggregateMonthlyMass($payslips);

        if ($monthly === []) {
            return [
                'historical' => [],
                'forecast' => [],
                'meta' => [
                    'method' => 'none',
                    'message' => 'Aucun bulletin de paie en base.',
                    'history_months_used' => 0,
                    'forecast_months' => $forecastMonths,
                    'scenario_percent' => $scenarioPercent,
                    'scenario_extra_net_monthly' => $scenarioExtraNetMonthly,
                    'chart_max' => 1.0,
                ],
                'ai' => $this->aiCommentService->buildComment([
                    'empty' => true,
                    'historical' => [],
                    'forecast' => [],
                    'meta' => ['method' => 'none'],
                ]),
            ];
        }

        $series = array_values($monthly);
        usort($series, static fn (array $a, array $b): int => strcmp($a['period'], $b['period']));

        $take = min(\count($series), $historyMonthsMax);
        $historySlice = \array_slice($series, -$take);

        $forecastPoints = $this->projectLinear(
            $historySlice,
            $forecastMonths,
            $scenarioPercent,
            $scenarioExtraNetMonthly,
        );

        $chartMax = 1.0;
        foreach ($historySlice as $row) {
            $chartMax = max($chartMax, (float) $row['total_net']);
        }
        foreach ($forecastPoints as $row) {
            $chartMax = max($chartMax, (float) $row['total_net']);
        }

        $meta = [
            'method' => 'linear_regression',
            'message' => '',
            'history_months_used' => \count($historySlice),
            'forecast_months' => $forecastMonths,
            'scenario_percent' => round($scenarioPercent, 2),
            'scenario_extra_net_monthly' => round($scenarioExtraNetMonthly, 2),
            'last_period' => $historySlice[\count($historySlice) - 1]['period'] ?? null,
            'chart_max' => round($chartMax, 2),
        ];

        $payloadForAi = [
            'empty' => false,
            'historical' => $historySlice,
            'forecast' => $forecastPoints,
            'meta' => $meta,
        ];

        return [
            'historical' => $historySlice,
            'forecast' => $forecastPoints,
            'meta' => $meta,
            'ai' => $this->aiCommentService->buildComment($payloadForAi),
        ];
    }

    /**
     * @param Payslip[] $payslips
     *
     * @return array<string, array{period: string, total_net: float, total_gross: float, payslip_count: int}>
     */
    private function aggregateMonthlyMass(array $payslips): array
    {
        $monthly = [];
        foreach ($payslips as $payslip) {
            $y = (int) $payslip->getYear();
            $m = (int) $payslip->getMonth();
            if ($y < 2000 || $m < 1 || $m > 12) {
                continue;
            }
            $key = sprintf('%04d-%02d', $y, $m);
            if (!isset($monthly[$key])) {
                $monthly[$key] = [
                    'period' => $key,
                    'total_net' => 0.0,
                    'total_gross' => 0.0,
                    'payslip_count' => 0,
                ];
            }
            $monthly[$key]['total_net'] += $payslip->getEstimatedNet();
            $monthly[$key]['total_gross'] += $payslip->getEstimatedGross();
            ++$monthly[$key]['payslip_count'];
        }

        return $monthly;
    }

    /**
     * @param list<array{period: string, total_net: float, total_gross: float, payslip_count: int}> $historySlice
     *
     * @return list<array{period: string, total_net: float, band_low: float, band_high: float}>
     */
    private function projectLinear(
        array $historySlice,
        int $horizon,
        float $scenarioPercent,
        float $scenarioExtraNetMonthly,
    ): array {
        $n = \count($historySlice);
        if ($n < 2) {
            $lastNet = (float) ($historySlice[0]['total_net'] ?? 0.0);
            $lastPeriod = (string) ($historySlice[0]['period'] ?? date('Y-m'));

            return $this->buildFuturePeriodsFlat($lastPeriod, $horizon, $lastNet, $scenarioPercent, $scenarioExtraNetMonthly);
        }

        $yVals = array_map(static fn (array $row): float => (float) $row['total_net'], $historySlice);
        $xVals = range(0, $n - 1);

        $meanX = array_sum($xVals) / $n;
        $meanY = array_sum($yVals) / $n;

        $num = 0.0;
        $den = 0.0;
        foreach ($xVals as $i => $x) {
            $num += ($x - $meanX) * ($yVals[$i] - $meanY);
            $den += ($x - $meanX) ** 2;
        }

        $b = $den > 1e-9 ? $num / $den : 0.0;
        $a = $meanY - $b * $meanX;

        $residuals = [];
        foreach ($xVals as $i => $x) {
            $pred = $a + $b * $x;
            $residuals[] = $yVals[$i] - $pred;
        }
        $variance = 0.0;
        foreach ($residuals as $r) {
            $variance += $r * $r;
        }
        $variance /= max(1, $n);
        $sigma = sqrt($variance);

        $lastPeriod = $historySlice[$n - 1]['period'];
        $out = [];
        for ($h = 1; $h <= $horizon; ++$h) {
            $xFuture = $n - 1 + $h;
            $yHat = $a + $b * $xFuture;
            $yHat = max(0.0, $yHat);
            $adj = $yHat * (1.0 + $scenarioPercent / 100.0) + $scenarioExtraNetMonthly;
            $adj = max(0.0, $adj);
            $band = max(500.0, 1.96 * $sigma);
            $periodStr = $this->addMonthsToPeriod($lastPeriod, $h);
            $out[] = [
                'period' => $periodStr,
                'total_net' => round($adj, 2),
                'band_low' => round(max(0.0, $adj - $band), 2),
                'band_high' => round($adj + $band, 2),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{period: string, total_net: float, band_low: float, band_high: float}>
     */
    private function buildFuturePeriodsFlat(
        string $lastPeriod,
        int $horizon,
        float $net,
        float $scenarioPercent,
        float $scenarioExtraNetMonthly,
    ): array {
        $out = [];
        for ($h = 1; $h <= $horizon; ++$h) {
            $adj = $net * (1.0 + $scenarioPercent / 100.0) + $scenarioExtraNetMonthly;
            $adj = max(0.0, $adj);
            $out[] = [
                'period' => $this->addMonthsToPeriod($lastPeriod, $h),
                'total_net' => round($adj, 2),
                'band_low' => round(max(0.0, $adj * 0.9), 2),
                'band_high' => round($adj * 1.1, 2),
            ];
        }

        return $out;
    }

    private function addMonthsToPeriod(string $period, int $addMonths): string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $period.'-01');
        if (false === $dt) {
            $dt = new \DateTimeImmutable('first day of this month');
        }
        $dt = $dt->modify('+'.$addMonths.' months');

        return $dt->format('Y-m');
    }
}

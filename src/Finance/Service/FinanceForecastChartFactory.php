<?php

declare(strict_types=1);

namespace App\Finance\Service;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class FinanceForecastChartFactory
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
    }

    /**
     * @param list<array{period: string, total_net: float}> $historical
     * @param list<array{period: string, total_net: float, band_low?: float, band_high?: float}> $forecast
     */
    public function createPayrollChart(array $historical, array $forecast): Chart
    {
        $labels = [];
        $histByPeriod = [];
        foreach ($historical as $row) {
            $p = (string) $row['period'];
            $labels[] = $p;
            $histByPeriod[$p] = round((float) $row['total_net'], 2);
        }

        $fcByPeriod = [];
        foreach ($forecast as $row) {
            $p = (string) $row['period'];
            if (!in_array($p, $labels, true)) {
                $labels[] = $p;
            }
            $fcByPeriod[$p] = round((float) $row['total_net'], 2);
        }

        $histData = [];
        $fcData = [];
        foreach ($labels as $p) {
            $histData[] = $histByPeriod[$p] ?? null;
            $fcData[] = $fcByPeriod[$p] ?? null;
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Historique (net)',
                    'data' => $histData,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99,102,241,0.08)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 4,
                    'spanGaps' => false,
                ],
                [
                    'label' => 'Prévision (net)',
                    'data' => $fcData,
                    'borderColor' => '#f59e0b',
                    'borderDash' => [6, 3],
                    'backgroundColor' => 'rgba(245,158,11,0.08)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 4,
                    'spanGaps' => false,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['position' => 'top'],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['callback' => '__function__(v) { return v.toLocaleString() + " TND"; }'],
                ],
            ],
        ]);

        return $chart;
    }
}

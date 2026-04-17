<?php

namespace App\Service\Finance;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Construit un graphique Chart.js (symfony/ux-chartjs) pour la prévision masse salariale.
 */
final class FinanceForecastChartFactory
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
    }

    /**
     * @param list<array{period: string, total_net: float, ...}> $historical
     * @param list<array{period: string, total_net: float, band_low: float, band_high: float}> $forecast
     */
    public function createMassPayrollChart(array $historical, array $forecast): Chart
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
            if (!\in_array($p, $labels, true)) {
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
                    'label' => 'Historique (net estimé, TND)',
                    'data' => $histData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                    'fill' => true,
                    'tension' => 0.25,
                    'spanGaps' => false,
                    'pointRadius' => 3,
                ],
                [
                    'label' => 'Prévision',
                    'data' => $fcData,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.08)',
                    'borderDash' => [6, 4],
                    'fill' => false,
                    'tension' => 0.25,
                    'spanGaps' => false,
                    'pointRadius' => 3,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['intersect' => false, 'mode' => 'index'],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'padding' => 16],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['maxRotation' => 45, 'minRotation' => 0],
                ],
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ]);

        $chart->setAttributes([
            'class' => 'max-h-80 w-full',
            'role' => 'img',
            'aria-label' => 'Historique et prévision de la masse salariale nette',
        ]);

        return $chart;
    }
}

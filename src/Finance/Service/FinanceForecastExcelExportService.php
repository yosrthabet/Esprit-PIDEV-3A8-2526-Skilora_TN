<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\User;

class FinanceForecastExcelExportService
{
    public function __construct(
        private readonly FinanceAnalyticsService $analyticsService,
    ) {
    }

    public function generateCsv(User $user): string
    {
        $forecast = $this->analyticsService->forecast($user);
        $historical = is_array($forecast['historical'] ?? null) ? $forecast['historical'] : [];
        $lines = [];
        $lines[] = implode(',', ['Type', 'Month', 'Amount']);

        /** @var list<string> $labels */
        $labels = is_array($historical['labels'] ?? null) ? $historical['labels'] : [];
        /** @var list<float> $values */
        $values = is_array($historical['values'] ?? null) ? $historical['values'] : [];
        foreach ($labels as $i => $label) {
            $lines[] = implode(',', ['Historical', $label, number_format($values[$i] ?? 0.0, 2, '.', '')]);
        }

        /** @var list<string> $fLabels */
        $fLabels = is_array($forecast['forecast_labels'] ?? null) ? $forecast['forecast_labels'] : [];
        /** @var list<float> $fValues */
        $fValues = is_array($forecast['forecast_values'] ?? null) ? $forecast['forecast_values'] : [];
        foreach ($fLabels as $i => $label) {
            $lines[] = implode(',', ['Forecast', $label, number_format($fValues[$i] ?? 0.0, 2, '.', '')]);
        }

        return implode("\n", $lines) . "\n";
    }
}

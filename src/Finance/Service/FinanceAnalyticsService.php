<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\User;
use App\Finance\Repository\ContractRepository;
use App\Finance\Repository\EscrowTransactionRepository;
use App\Finance\Repository\InvoiceRepository;
use App\Finance\Repository\WalletRepository;

class FinanceAnalyticsService
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly EscrowTransactionRepository $transactionRepository,
        private readonly WalletRepository $walletRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardKpis(User $user): array
    {
        $contracts = $this->contractRepository->findForUser($user);
        $invoices = $this->invoiceRepository->findForUser($user);
        $wallet = $this->walletRepository->findOneBy(['user' => $user]);

        $totalEarned = 0.0;
        $totalPending = 0.0;
        foreach ($invoices as $inv) {
            $amount = (float) ($inv->getAmount() ?? 0);
            if ($inv->getPaidAt() !== null) {
                $totalEarned += $amount;
            } else {
                $totalPending += $amount;
            }
        }

        return [
            'total_contracts' => count($contracts),
            'total_invoices' => count($invoices),
            'total_transactions' => count($this->transactionRepository->findForUser($user, 9999)),
            'total_earned' => number_format($totalEarned, 2, '.', ''),
            'total_pending' => number_format($totalPending, 2, '.', ''),
            'wallet_balance' => $wallet?->getBalance() ?? '0.00',
            'currency' => $wallet?->getCurrency()->value ?? 'TND',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function monthlyBreakdown(User $user, int $months = 6): array
    {
        $invoices = $this->invoiceRepository->findForUser($user);
        $now = new \DateTimeImmutable();
        /** @var array<string, float> $breakdown */
        $breakdown = [];

        for ($i = $months - 1; $i >= 0; --$i) {
            $key = $now->modify("-{$i} months")->format('Y-m');
            $breakdown[$key] = 0.0;
        }

        foreach ($invoices as $inv) {
            if ($inv->getPaidAt() === null) {
                continue;
            }
            $key = $inv->getPaidAt()->format('Y-m');
            if (isset($breakdown[$key])) {
                $breakdown[$key] += (float) ($inv->getAmount() ?? 0);
            }
        }

        return [
            'labels' => array_keys($breakdown),
            'values' => array_values($breakdown),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forecast(User $user, int $forecastMonths = 3, float $scenarioPercent = 0.0): array
    {
        $monthly = $this->monthlyBreakdown($user, 12);
        /** @var list<float> $values */
        $values = $monthly['values'];
        $n = count($values);

        $now = new \DateTimeImmutable();
        $forecastLabels = [];
        $forecastValues = [];
        $bandLow = [];
        $bandHigh = [];

        if ($n < 2) {
            $avg = $n > 0 ? $values[0] : 0.0;
            for ($i = 1; $i <= $forecastMonths; ++$i) {
                $adj = $avg * (1.0 + $scenarioPercent / 100.0);
                $forecastLabels[] = $now->modify("+{$i} months")->format('Y-m');
                $forecastValues[] = round(max(0.0, $adj), 2);
                $bandLow[] = round(max(0.0, $adj * 0.9), 2);
                $bandHigh[] = round($adj * 1.1, 2);
            }

            return [
                'historical' => $monthly,
                'forecast_labels' => $forecastLabels,
                'forecast_values' => $forecastValues,
                'band_low' => $bandLow,
                'band_high' => $bandHigh,
                'method' => $n === 0 ? 'none' : 'flat',
                'scenario_percent' => $scenarioPercent,
            ];
        }

        $xVals = range(0, $n - 1);
        $meanX = array_sum($xVals) / $n;
        $meanY = array_sum($values) / $n;

        $num = 0.0;
        $den = 0.0;
        foreach ($xVals as $i => $x) {
            $num += ($x - $meanX) * ($values[$i] - $meanY);
            $den += ($x - $meanX) ** 2;
        }

        $b = $den > 1e-9 ? $num / $den : 0.0;
        $a = $meanY - $b * $meanX;

        $variance = 0.0;
        foreach ($xVals as $i => $x) {
            $residual = $values[$i] - ($a + $b * $x);
            $variance += $residual * $residual;
        }
        $sigma = sqrt($variance / max(1, $n));

        for ($h = 1; $h <= $forecastMonths; ++$h) {
            $xFuture = $n - 1 + $h;
            $yHat = max(0.0, $a + $b * $xFuture);
            $adj = max(0.0, $yHat * (1.0 + $scenarioPercent / 100.0));
            $band = max(200.0, 1.96 * $sigma);
            $forecastLabels[] = $now->modify("+{$h} months")->format('Y-m');
            $forecastValues[] = round($adj, 2);
            $bandLow[] = round(max(0.0, $adj - $band), 2);
            $bandHigh[] = round($adj + $band, 2);
        }

        return [
            'historical' => $monthly,
            'forecast_labels' => $forecastLabels,
            'forecast_values' => $forecastValues,
            'band_low' => $bandLow,
            'band_high' => $bandHigh,
            'method' => 'linear_regression',
            'slope' => round($b, 4),
            'intercept' => round($a, 4),
            'sigma' => round($sigma, 2),
            'scenario_percent' => $scenarioPercent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function calculateTaxes(float $grossMonthlySalary): array
    {
        $grossMonthlySalary = max(0.0, $grossMonthlySalary);
        $cnssEmployee = round($grossMonthlySalary * 0.0918, 2);
        $cnssEmployer = round($grossMonthlySalary * 0.165, 2);
        $taxableAnnual = max(0.0, ($grossMonthlySalary - $cnssEmployee) * 12);

        $bands = [
            [0.0, 5000.0, 0.00],
            [5000.0, 20000.0, 0.26],
            [20000.0, 30000.0, 0.28],
            [30000.0, 50000.0, 0.32],
            [50000.0, null, 0.35],
        ];

        $irppAnnual = 0.0;
        $bandDetails = [];
        foreach ($bands as [$min, $max, $rate]) {
            if ($taxableAnnual <= $min) {
                continue;
            }
            $upper = $max ?? $taxableAnnual;
            $portion = min($taxableAnnual, $upper) - $min;
            $portionTax = $portion * $rate;
            $irppAnnual += $portionTax;
            $bandDetails[] = [
                'min' => $min,
                'max' => $max,
                'rate_percent' => $rate * 100,
                'taxable_amount' => round($portion, 2),
                'tax_amount' => round($portionTax, 2),
            ];
        }

        $irppMonthly = round($irppAnnual / 12, 2);
        $netMonthly = round(max(0.0, $grossMonthlySalary - $cnssEmployee - $irppMonthly), 2);

        return [
            'gross_monthly' => round($grossMonthlySalary, 2),
            'cnss_employee' => $cnssEmployee,
            'cnss_employer' => $cnssEmployer,
            'taxable_annual' => round($taxableAnnual, 2),
            'irpp_annual' => round($irppAnnual, 2),
            'irpp_monthly' => $irppMonthly,
            'net_monthly' => $netMonthly,
            'effective_tax_rate_percent' => $grossMonthlySalary > 0 ? round((($grossMonthlySalary - $netMonthly) / $grossMonthlySalary) * 100, 2) : 0.0,
            'irpp_breakdown' => $bandDetails,
        ];
    }
}

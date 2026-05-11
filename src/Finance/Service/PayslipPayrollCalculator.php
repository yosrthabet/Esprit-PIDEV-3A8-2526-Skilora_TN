<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\User;
use App\Enum\Currency;
use App\Finance\Entity\Payslip;

class PayslipPayrollCalculator
{
    public const CNSS_EMPLOYEE_RATE = 0.0918;
    public const CNSS_EMPLOYER_RATE = 0.165;

    public function calculate(User $employee, string $grossAmount, Currency $currency, ?\DateTimeImmutable $periodStart = null, ?\DateTimeImmutable $periodEnd = null): Payslip
    {
        $result = $this->computeFromGross((float) $grossAmount);

        $payslip = new Payslip();
        $payslip->setEmployee($employee);
        $payslip->setGrossAmount(number_format($result['gross'], 2, '.', ''));
        $payslip->setNetAmount(number_format($result['net'], 2, '.', ''));
        $payslip->setCurrency($currency);

        if ($periodStart !== null) {
            $payslip->setPeriodStart($periodStart);
        }
        if ($periodEnd !== null) {
            $payslip->setPeriodEnd($periodEnd);
        }

        return $payslip;
    }

    /**
     * @return array{overtime_total: float, gross: float, cnss_employee: float, cnss_employer: float, irpp: float, other_deductions: float, total_deductions: float, net: float, effective_tax_rate_percent: float}
     */
    public function computeFromComponents(
        float $baseSalary,
        float $overtimeHours = 0.0,
        float $overtimeRatePerHour = 0.0,
        float $bonuses = 0.0,
        float $otherDeductions = 0.0,
    ): array {
        $overtimeTotal = round(max(0.0, $overtimeHours) * max(0.0, $overtimeRatePerHour), 2);
        $gross = round(max(0.0, $baseSalary) + $overtimeTotal + max(0.0, $bonuses), 2);

        $result = $this->computeFromGross($gross, $otherDeductions);
        $result['overtime_total'] = $overtimeTotal;

        return $result;
    }

    /**
     * @return array{overtime_total: float, gross: float, cnss_employee: float, cnss_employer: float, irpp: float, other_deductions: float, total_deductions: float, net: float, effective_tax_rate_percent: float}
     */
    public function computeFromGross(float $grossMonthly, float $otherDeductions = 0.0): array
    {
        $grossMonthly = max(0.0, $grossMonthly);
        $otherDeductions = max(0.0, $otherDeductions);

        $cnssEmployee = round($grossMonthly * self::CNSS_EMPLOYEE_RATE, 2);
        $cnssEmployer = round($grossMonthly * self::CNSS_EMPLOYER_RATE, 2);
        $taxableMonthly = max(0.0, $grossMonthly - $cnssEmployee);
        $irpp = $this->computeMonthlyIrpp($taxableMonthly);
        $totalDed = round($cnssEmployee + $irpp + $otherDeductions, 2);
        $net = round(max(0.0, $grossMonthly - $totalDed), 2);
        $eff = $grossMonthly > 0 ? round(min(100.0, ($totalDed / $grossMonthly) * 100.0), 2) : 0.0;

        return [
            'overtime_total' => 0.0,
            'gross' => round($grossMonthly, 2),
            'cnss_employee' => $cnssEmployee,
            'cnss_employer' => $cnssEmployer,
            'irpp' => round($irpp, 2),
            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => $totalDed,
            'net' => $net,
            'effective_tax_rate_percent' => $eff,
        ];
    }

    /**
     * @return array{gross_monthly: float, cnss_employee: float, cnss_employer: float, taxable_annual: float, irpp_annual: float, irpp_monthly: float, net_monthly: float, effective_tax_rate_percent: float, irpp_breakdown: list<array<string, float|null>>}
     */
    public function calculateTaxes(float $grossMonthlySalary): array
    {
        $grossMonthlySalary = max(0.0, $grossMonthlySalary);

        $cnssEmployee = $grossMonthlySalary * self::CNSS_EMPLOYEE_RATE;
        $cnssEmployer = $grossMonthlySalary * self::CNSS_EMPLOYER_RATE;
        $taxableAnnual = max(0.0, ($grossMonthlySalary - $cnssEmployee) * 12);
        $irppBreakdown = $this->calculateProgressiveIrppBreakdown($taxableAnnual);
        $irppAnnual = $irppBreakdown['total_irpp'];
        $irppMonthly = $irppAnnual / 12;
        $netMonthly = max(0.0, $grossMonthlySalary - $cnssEmployee - $irppMonthly);

        return [
            'gross_monthly' => round($grossMonthlySalary, 2),
            'cnss_employee' => round($cnssEmployee, 2),
            'cnss_employer' => round($cnssEmployer, 2),
            'taxable_annual' => round($taxableAnnual, 2),
            'irpp_annual' => round($irppAnnual, 2),
            'irpp_monthly' => round($irppMonthly, 2),
            'net_monthly' => round($netMonthly, 2),
            'effective_tax_rate_percent' => $grossMonthlySalary > 0 ? round((($grossMonthlySalary - $netMonthly) / $grossMonthlySalary) * 100, 2) : 0.0,
            'irpp_breakdown' => $irppBreakdown['bands'],
        ];
    }

    private function computeMonthlyIrpp(float $taxableMonthly): float
    {
        $taxableAnnual = $taxableMonthly * 12;
        $breakdown = $this->calculateProgressiveIrppBreakdown($taxableAnnual);

        return round($breakdown['total_irpp'] / 12, 2);
    }

    /**
     * @return array{total_irpp: float, bands: list<array<string, float|null>>}
     */
    private function calculateProgressiveIrppBreakdown(float $taxableAnnual): array
    {
        $bands = [
            [0.0, 5000.0, 0.00],
            [5000.0, 20000.0, 0.26],
            [20000.0, 30000.0, 0.28],
            [30000.0, 50000.0, 0.32],
            [50000.0, null, 0.35],
        ];

        $taxableAnnual = max(0.0, $taxableAnnual);
        $tax = 0.0;
        $details = [];

        foreach ($bands as [$min, $max, $rate]) {
            if ($taxableAnnual <= $min) {
                continue;
            }

            $upperBound = $max ?? $taxableAnnual;
            $portion = min($taxableAnnual, $upperBound) - $min;
            $portionTax = 0.0;
            if ($portion > 0) {
                $portionTax = $portion * $rate;
                $tax += $portionTax;
            }

            $details[] = [
                'min' => $min,
                'max' => $max,
                'rate_percent' => $rate * 100,
                'taxable_amount' => round($portion, 2),
                'tax_amount' => round($portionTax, 2),
            ];
        }

        return [
            'total_irpp' => max(0.0, $tax),
            'bands' => $details,
        ];
    }
}

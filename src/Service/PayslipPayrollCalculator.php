<?php

namespace App\Service;

/**
 * Calcul bulletin de paie aligné sur le module JavaFX (FinanceController + PayslipRow).
 * brut = base + heures_sup_total + primes, CNSS = brut × 9,18 %, IRPP = (brut − CNSS) × 26 %.
 */
final class PayslipPayrollCalculator
{
    public const CNSS_RATE = 0.0918;

    /** Taux IRPP simplifié UI Java sur la base imposable mensuelle (brut − CNSS). */
    public const IRPP_ON_TAXABLE_RATE = 0.26;

    /**
     * @return array{
     *     overtime_total: float,
     *     gross: float,
     *     cnss: float,
     *     irpp: float,
     *     other_deductions: float,
     *     total_deductions: float,
     *     net: float,
     *     effective_tax_rate_percent: float
     * }
     */
    public function computeFromComponents(
        float $baseSalary,
        float $overtimeHours,
        float $overtimeRatePerHour,
        float $bonuses,
        float $otherDeductions,
    ): array {
        $overtimeTotal = round(max(0.0, $overtimeHours) * max(0.0, $overtimeRatePerHour), 2);
        $gross = round(max(0.0, $baseSalary) + $overtimeTotal + max(0.0, $bonuses), 2);

        return $this->computeFromGross($gross, $otherDeductions, $overtimeTotal);
    }

    /**
     * @return array{
     *     overtime_total: float|null,
     *     gross: float,
     *     cnss: float,
     *     irpp: float,
     *     other_deductions: float,
     *     total_deductions: float,
     *     net: float,
     *     effective_tax_rate_percent: float
     * }
     */
    public function computeFromGross(float $grossMonthly, float $otherDeductions = 0.0, ?float $overtimeTotalBreakdown = null): array
    {
        $grossMonthly = max(0.0, $grossMonthly);
        $otherDeductions = max(0.0, $otherDeductions);

        $cnss = round($grossMonthly * self::CNSS_RATE, 2);
        $taxable = max(0.0, $grossMonthly - $cnss);
        $irpp = round($taxable * self::IRPP_ON_TAXABLE_RATE, 2);
        $totalDed = round($cnss + $irpp + $otherDeductions, 2);
        $net = round(max(0.0, $grossMonthly - $totalDed), 2);
        $eff = $grossMonthly > 0 ? round(min(100.0, ($totalDed / $grossMonthly) * 100.0), 2) : 0.0;

        return [
            'overtime_total' => null !== $overtimeTotalBreakdown ? round($overtimeTotalBreakdown, 2) : null,
            'gross' => round($grossMonthly, 2),
            'cnss' => $cnss,
            'irpp' => $irpp,
            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => $totalDed,
            'net' => $net,
            'effective_tax_rate_percent' => $eff,
        ];
    }
}

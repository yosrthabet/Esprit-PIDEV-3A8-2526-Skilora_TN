<?php

namespace App\Service;

use App\Entity\Finance\Payslip;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class FinancePdfExportService
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $reportData
     */
    public function buildEmployeeReportPdf(array $reportData): string
    {
        $html = $this->twig->render('finance/report/employee_report.html.twig', [
            'report' => $reportData,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    public function buildPayslipPdf(Payslip $payslip, PayslipPayrollCalculator $calculator): string
    {
        $hours = (float) ($payslip->getOvertimeHours() ?? 0);
        $totalOt = (float) ($payslip->getOvertimeTotal() ?? 0);
        $rate = $hours > 0 ? $totalOt / $hours : 0.0;
        $breakdown = $calculator->computeFromComponents(
            (float) ($payslip->getBaseSalary() ?? 0),
            $hours,
            $rate,
            (float) ($payslip->getBonuses() ?? 0),
            (float) ($payslip->getOtherDeductions() ?? 0),
        );

        $user = $payslip->getUser();
        $html = $this->twig->render('finance/report/payslip_slip.html.twig', [
            'p' => $payslip,
            'employee_name' => $user ? ($user->getFullName() ?: $user->getUsername()) : '—',
            'calc' => $breakdown,
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }
}

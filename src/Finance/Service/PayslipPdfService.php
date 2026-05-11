<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Finance\Entity\Payslip;
use Dompdf\Dompdf;
use Twig\Environment;

class PayslipPdfService
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function generate(Payslip $payslip): string
    {
        $html = $this->twig->render('finance/payslip/pdf.html.twig', [
            'payslip' => $payslip,
        ]);

        $dompdf = new Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output() ?: '';
    }
}

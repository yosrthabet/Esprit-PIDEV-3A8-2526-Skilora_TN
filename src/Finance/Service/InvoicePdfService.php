<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Finance\Entity\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;

final class InvoicePdfService
{
    public function generatePdf(Invoice $invoice): string
    {
        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($invoice));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output() ?: '';
    }

    private function buildHtml(Invoice $invoice): string
    {
        $contract = $invoice->getContract();
        $application = $contract->getHireOffer()->getApplication();
        $jobTitle = $application->getJobOffer()->getTitle();
        $companyLabel = $application->getJobOffer()->getCompanyLabel();

        $issuerName = $invoice->getIssuer()->getDisplayName();
        $issuerEmail = $invoice->getIssuer()->getEmail();
        $recipientName = $invoice->getRecipient()->getDisplayName();
        $recipientEmail = $invoice->getRecipient()->getEmail();

        $number = htmlspecialchars($invoice->getNumber(), ENT_QUOTES);
        $amount = $invoice->getAmount() ?: '0.00';
        $currency = $invoice->getCurrency()->value;
        $status = $invoice->getStatus()->label();
        $issuedAt = $invoice->getIssuedAt()->format('F d, Y');
        $paidAt = $invoice->getPaidAt()?->format('F d, Y') ?? '—';
        $contractId = $contract->getId();

        $jobTitleSafe = htmlspecialchars($jobTitle, ENT_QUOTES);
        $companyLabelSafe = htmlspecialchars($companyLabel, ENT_QUOTES);
        $issuerNameSafe = htmlspecialchars($issuerName, ENT_QUOTES);
        $issuerEmailSafe = htmlspecialchars($issuerEmail ?? '', ENT_QUOTES);
        $recipientNameSafe = htmlspecialchars($recipientName, ENT_QUOTES);
        $recipientEmailSafe = htmlspecialchars($recipientEmail ?? '', ENT_QUOTES);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; color: #18181b; font-size: 10pt; line-height: 1.5; }

    .page { padding: 50px 55px; position: relative; min-height: 100%; }

    /* Header band */
    .header { display: table; width: 100%; margin-bottom: 40px; }
    .header-left { display: table-cell; vertical-align: top; width: 55%; }
    .header-right { display: table-cell; vertical-align: top; width: 45%; text-align: right; }
    .brand { font-size: 28pt; font-weight: bold; color: #6366f1; letter-spacing: -0.5px; }
    .brand-sub { font-size: 8pt; color: #71717a; margin-top: 2px; letter-spacing: 1px; text-transform: uppercase; }
    .inv-label { font-size: 7pt; font-weight: bold; color: #a1a1aa; letter-spacing: 2.5px; text-transform: uppercase; }
    .inv-number { font-size: 16pt; font-weight: bold; color: #18181b; margin-top: 4px; }
    .inv-date { font-size: 9pt; color: #71717a; margin-top: 6px; }

    /* Status badge */
    .status { display: inline-block; padding: 3px 14px; border-radius: 20px; font-size: 8pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
    .status-paid { background: #dcfce7; color: #166534; }
    .status-issued { background: #fef3c7; color: #92400e; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }

    /* Parties */
    .parties { display: table; width: 100%; margin-bottom: 35px; }
    .party { display: table-cell; width: 50%; vertical-align: top; }
    .party-label { font-size: 7pt; font-weight: bold; color: #a1a1aa; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px; }
    .party-name { font-size: 12pt; font-weight: bold; color: #18181b; }
    .party-email { font-size: 9pt; color: #71717a; margin-top: 2px; }

    /* Divider */
    .divider { border: none; border-top: 1px solid #e4e4e7; margin: 25px 0; }

    /* Table */
    .items { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .items thead th { font-size: 7pt; font-weight: bold; color: #a1a1aa; letter-spacing: 2px; text-transform: uppercase; padding: 12px 16px; border-bottom: 2px solid #e4e4e7; text-align: left; }
    .items thead th.right { text-align: right; }
    .items tbody td { padding: 16px; border-bottom: 1px solid #f4f4f5; vertical-align: top; }
    .items tbody td.right { text-align: right; }
    .item-title { font-weight: bold; font-size: 10pt; }
    .item-meta { font-size: 8pt; color: #71717a; margin-top: 3px; }

    /* Totals */
    .totals { width: 100%; margin-top: 0; }
    .totals-row { display: table; width: 100%; border-top: 2px solid #18181b; padding: 16px 0; }
    .totals-label { display: table-cell; text-align: right; padding-right: 16px; font-size: 8pt; font-weight: bold; color: #71717a; letter-spacing: 2px; text-transform: uppercase; vertical-align: middle; }
    .totals-value { display: table-cell; text-align: right; font-size: 18pt; font-weight: bold; color: #18181b; width: 200px; }

    /* Footer */
    .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 20px 55px; border-top: 1px solid #e4e4e7; }
    .footer-inner { display: table; width: 100%; }
    .footer-left { display: table-cell; font-size: 7.5pt; color: #a1a1aa; vertical-align: middle; }
    .footer-right { display: table-cell; text-align: right; font-size: 7.5pt; color: #a1a1aa; vertical-align: middle; }

    /* Watermark for paid */
    .watermark { position: fixed; top: 40%; left: 15%; font-size: 100pt; font-weight: bold; color: rgba(22, 163, 74, 0.06); transform: rotate(-30deg); letter-spacing: 20px; z-index: 0; }
</style>
</head>
<body>
<div class="page">

    {$this->watermark($invoice)}

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="brand">Skilora</div>
            <div class="brand-sub">Professional Freelancing Platform</div>
        </div>
        <div class="header-right">
            <div class="inv-label">Invoice</div>
            <div class="inv-number">{$number}</div>
            <div class="inv-date">
                Issued: {$issuedAt}<br>
                Paid: {$paidAt}
            </div>
            <div style="margin-top: 10px;">
                <span class="status {$this->statusClass($invoice)}">{$status}</span>
            </div>
        </div>
    </div>

    <!-- Parties -->
    <div class="parties">
        <div class="party">
            <div class="party-label">From</div>
            <div class="party-name">{$issuerNameSafe}</div>
            <div class="party-email">{$issuerEmailSafe}</div>
        </div>
        <div class="party">
            <div class="party-label">Bill To</div>
            <div class="party-name">{$recipientNameSafe}</div>
            <div class="party-email">{$recipientEmailSafe}</div>
        </div>
    </div>

    <hr class="divider">

    <!-- Line Items -->
    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th>Contract</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="item-title">Service delivery</div>
                    <div class="item-meta">{$jobTitleSafe} &mdash; {$companyLabelSafe}</div>
                </td>
                <td>
                    <div class="item-meta">#{$contractId}</div>
                </td>
                <td class="right" style="font-weight:bold; font-size:11pt;">{$amount} {$currency}</td>
            </tr>
        </tbody>
    </table>

    <!-- Total -->
    <div class="totals-row">
        <div class="totals-label">Total Due</div>
        <div class="totals-value">{$amount} {$currency}</div>
    </div>

    <hr class="divider">

    <!-- Payment info -->
    <div style="font-size: 8pt; color: #71717a; margin-top: 15px;">
        <strong style="color: #18181b;">Payment Information</strong><br>
        All payments are processed through the Skilora wallet system.<br>
        Transaction reference: {$number}<br>
        Currency: {$currency} (Tunisian Dinar)
    </div>

</div>

<!-- Footer -->
<div class="footer">
    <div class="footer-inner">
        <div class="footer-left">Skilora &middot; skilora.dev &middot; hello@skilora.dev</div>
        <div class="footer-right">Invoice {$number} &middot; Page 1 of 1</div>
    </div>
</div>

</body>
</html>
HTML;
    }

    private function watermark(Invoice $invoice): string
    {
        if ($invoice->getStatus() === \App\Enum\InvoiceStatus::PAID) {
            return '<div class="watermark">PAID</div>';
        }
        return '';
    }

    private function statusClass(Invoice $invoice): string
    {
        return match ($invoice->getStatus()) {
            \App\Enum\InvoiceStatus::PAID => 'status-paid',
            \App\Enum\InvoiceStatus::VOID => 'status-cancelled',
            default => 'status-issued',
        };
    }
}

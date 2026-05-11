<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Service\FinanceExportService;
use App\Finance\Service\FinanceForecastExcelExportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FinanceExportController extends AppController
{
    public function __construct(
        private readonly FinanceExportService $exportService,
        private readonly FinanceForecastExcelExportService $forecastExportService,
    ) {
    }

    #[Route('/finance/export/contracts', name: 'app_finance_export_contracts', methods: ['GET'])]
    public function exportContracts(): Response
    {
        $csv = $this->exportService->toCsvString($this->exportService->contractsCsv($this->getAppUser()));

        return $this->csvResponse($csv, 'contracts.csv');
    }

    #[Route('/finance/export/invoices', name: 'app_finance_export_invoices', methods: ['GET'])]
    public function exportInvoices(): Response
    {
        $csv = $this->exportService->toCsvString($this->exportService->invoicesCsv($this->getAppUser()));

        return $this->csvResponse($csv, 'invoices.csv');
    }

    #[Route('/finance/export/transactions', name: 'app_finance_export_transactions', methods: ['GET'])]
    public function exportTransactions(): Response
    {
        $csv = $this->exportService->toCsvString($this->exportService->transactionsCsv($this->getAppUser()));

        return $this->csvResponse($csv, 'transactions.csv');
    }

    #[Route('/finance/export/forecast', name: 'app_finance_export_forecast', methods: ['GET'])]
    public function exportForecast(): Response
    {
        $csv = $this->forecastExportService->generateCsv($this->getAppUser());

        return $this->csvResponse($csv, 'finance_forecast.csv');
    }

    private function csvResponse(string $csv, string $filename): Response
    {
        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

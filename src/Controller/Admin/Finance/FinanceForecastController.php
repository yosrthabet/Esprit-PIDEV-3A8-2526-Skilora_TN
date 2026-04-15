<?php

namespace App\Controller\Admin\Finance;

use App\Service\Finance\FinanceForecastChartFactory;
use App\Service\Finance\FinanceForecastExcelExportService;
use App\Service\Finance\FinanceForecastService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/finance/forecast')]
#[IsGranted('ROLE_ADMIN')]
final class FinanceForecastController extends AbstractController
{
    #[Route('', name: 'admin_finance_forecast', methods: ['GET'])]
    public function index(
        Request $request,
        FinanceForecastService $forecastService,
        FinanceForecastChartFactory $forecastChartFactory,
    ): Response {
        $historyMonths = (int) $request->query->get('history', 12);
        $forecastMonths = (int) $request->query->get('ahead', 3);
        $scenarioPct = (float) $request->query->get('pct', 0);
        $scenarioExtra = (float) $request->query->get('extra', 0);

        $data = $forecastService->buildForecast($historyMonths, $forecastMonths, $scenarioPct, $scenarioExtra);

        $forecastChart = null;
        if ([] !== $data['historical']) {
            $forecastChart = $forecastChartFactory->createMassPayrollChart($data['historical'], $data['forecast']);
        }

        return $this->render('admin/finance/forecast/index.html.twig', [
            'page_title' => 'Prévision masse salariale',
            'forecast_data' => $data,
            'forecast_chart' => $forecastChart,
            'form_history' => $historyMonths,
            'form_ahead' => $forecastMonths,
            'form_pct' => $scenarioPct,
            'form_extra' => $scenarioExtra,
        ]);
    }

    #[Route('/export.xlsx', name: 'admin_finance_forecast_export', methods: ['GET'])]
    public function export(
        Request $request,
        FinanceForecastService $forecastService,
        FinanceForecastExcelExportService $excelExportService,
    ): Response {
        $historyMonths = (int) $request->query->get('history', 12);
        $forecastMonths = (int) $request->query->get('ahead', 3);
        $scenarioPct = (float) $request->query->get('pct', 0);
        $scenarioExtra = (float) $request->query->get('extra', 0);

        $data = $forecastService->buildForecast($historyMonths, $forecastMonths, $scenarioPct, $scenarioExtra);

        $spreadsheet = $excelExportService->buildSpreadsheet([
            'historical' => $data['historical'],
            'forecast' => $data['forecast'],
            'meta' => $data['meta'],
        ]);

        $filename = 'prevision_masse_salariale_'.date('Y-m-d_His').'.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Service\FinanceAnalyticsService;
use App\Finance\Service\PayslipPayrollCalculator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FinanceAnalyticsController extends AppController
{
    public function __construct(
        private readonly FinanceAnalyticsService $analyticsService,
        private readonly PayslipPayrollCalculator $calculator,
    ) {
    }

    #[Route('/finance/analytics', name: 'app_finance_analytics', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getAppUser();

        return $this->render('finance/analytics/index.html.twig', [
            'kpis' => $this->analyticsService->dashboardKpis($user),
            'monthly' => $this->analyticsService->monthlyBreakdown($user),
            'forecast' => $this->analyticsService->forecast($user),
        ]);
    }

    #[Route('/finance/analytics/api/kpis', name: 'app_finance_analytics_kpis', methods: ['GET'])]
    public function kpisApi(): JsonResponse
    {
        return $this->json($this->analyticsService->dashboardKpis($this->getAppUser()));
    }

    #[Route('/finance/analytics/api/forecast', name: 'app_finance_analytics_forecast', methods: ['GET'])]
    public function forecastApi(Request $request): JsonResponse
    {
        $scenario = (float) $request->query->get('scenario', '0');

        return $this->json($this->analyticsService->forecast($this->getAppUser(), 3, $scenario));
    }

    #[Route('/finance/calculator', name: 'app_finance_calculator', methods: ['GET'])]
    public function calculator(): Response
    {
        return $this->render('finance/calculator/index.html.twig');
    }

    #[Route('/finance/calculator/api/taxes', name: 'app_finance_calculator_taxes', methods: ['POST'])]
    public function taxesApi(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $gross = (float) ($data['gross_monthly'] ?? 0);

        return $this->json($this->analyticsService->calculateTaxes($gross));
    }

    #[Route('/finance/calculator/api/payroll', name: 'app_finance_calculator_payroll', methods: ['POST'])]
    public function payrollApi(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];

        return $this->json($this->calculator->computeFromComponents(
            (float) ($data['base_salary'] ?? 0),
            (float) ($data['overtime_hours'] ?? 0),
            (float) ($data['overtime_rate'] ?? 0),
            (float) ($data['bonuses'] ?? 0),
            (float) ($data['other_deductions'] ?? 0),
        ));
    }
}

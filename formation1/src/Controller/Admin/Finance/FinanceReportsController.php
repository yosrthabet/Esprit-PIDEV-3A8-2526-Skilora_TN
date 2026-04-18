<?php

namespace App\Controller\Admin\Finance;

use App\Repository\UserRepository;
use App\Service\FinanceAnalyticsService;
use App\Service\FinancePdfExportService;
use App\Service\PayslipPayrollCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/finance/reports')]
#[IsGranted('ROLE_ADMIN')]
final class FinanceReportsController extends AbstractController
{
    public function __construct(
        private readonly FinanceAnalyticsService $analyticsService,
        private readonly FinancePdfExportService $pdfExportService,
    ) {
    }

    #[Route('', name: 'admin_finance_reports', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, PayslipPayrollCalculator $payslipPayrollCalculator): Response
    {
        $grossInput = (string) $request->query->get('gross', '');
        $taxEstimate = null;
        if ($grossInput !== '' && is_numeric($grossInput)) {
            $taxEstimate = $this->analyticsService->calculateTaxes((float) $grossInput);
        }

        $simpGrossStr = (string) $request->query->get('simp_gross', '');
        $simpCurrency = (string) $request->query->get('simp_currency', 'TND');
        $simpTax = null;
        if ($simpGrossStr !== '' && is_numeric($simpGrossStr)) {
            $simpTax = $payslipPayrollCalculator->computeFromGross((float) $simpGrossStr, 0.0);
        }

        return $this->render('admin/finance/reports/index.html.twig', [
            'page_title' => 'Rapports & outils',
            'tax_estimate' => $taxEstimate,
            'gross_input' => $grossInput,
            'simp_gross_input' => $simpGrossStr,
            'simp_currency' => $simpCurrency,
            'simp_tax' => $simpTax,
            'employees_for_export' => $userRepository->findAllOrderedByName(),
            'admin_export_pdf_sample_path' => $this->generateUrl('admin_finance_reports_export_employee_pdf', ['id' => 1]),
        ]);
    }

    #[Route('/api/overview', name: 'admin_finance_reports_api_overview', methods: ['GET'])]
    public function apiOverview(): JsonResponse
    {
        return $this->json($this->analyticsService->getOverview());
    }

    #[Route('/api/tax-estimate', name: 'admin_finance_reports_api_tax_estimate', methods: ['GET'])]
    public function apiTaxEstimate(Request $request): JsonResponse
    {
        $gross = (float) $request->query->get('gross', 0);
        if ($gross <= 0) {
            return $this->json(['error' => 'Le paramètre "gross" doit être strictement positif.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->analyticsService->calculateTaxes($gross));
    }

    /** Même logique que bulletins JavaFX : CNSS 9,18 % + IRPP 26 % sur (brut − CNSS). */
    #[Route('/api/tax-simplified', name: 'admin_finance_reports_api_tax_simplified', methods: ['GET'])]
    public function apiTaxSimplified(Request $request, PayslipPayrollCalculator $payslipPayrollCalculator): JsonResponse
    {
        $gross = (float) $request->query->get('gross', 0);
        if ($gross <= 0) {
            return $this->json(['error' => 'Le paramètre "gross" doit être strictement positif.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($payslipPayrollCalculator->computeFromGross($gross, 0.0));
    }

    #[Route('/api/employee/{id}', name: 'admin_finance_reports_api_employee', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function apiEmployee(int $id): JsonResponse
    {
        $summary = $this->analyticsService->getEmployeeSummary($id);
        if (null === $summary) {
            return $this->json(['error' => 'Employé introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($summary);
    }

    #[Route('/export/employee/{id}.pdf', name: 'admin_finance_reports_export_employee_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportEmployeePdf(int $id): Response
    {
        $reportData = $this->analyticsService->getEmployeeReportData($id);
        if (null === $reportData) {
            throw $this->createNotFoundException('Employé introuvable.');
        }

        $pdf = $this->pdfExportService->buildEmployeeReportPdf($reportData);
        $safeName = preg_replace('/[^a-z0-9\-]+/i', '_', (string) $reportData['employee']['full_name']) ?? 'employee';

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="rapport_finance_%s.pdf"', strtolower($safeName)),
        ]);
    }
}

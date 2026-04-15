<?php

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Service\EmployerContext;
use App\Repository\UserRepository;
use App\Service\FinanceAnalyticsService;
use App\Service\FinancePdfExportService;
use App\Service\PayslipPayrollCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employer', name: 'app_employer_')]
#[IsGranted('ROLE_EMPLOYER')]
final class EmployerDashboardController extends AbstractController
{
    /**
     * Sans extension PHP intl, le polyfill Symfony ne gère pas fr_FR — on formate donc à la main.
     */
    private function formatFrenchDateForEmployerDashboard(\DateTimeInterface $date): string
    {
        if (extension_loaded('intl')) {
            $fmt = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, $date instanceof \DateTimeImmutable ? $date->getTimezone()->getName() : 'Africa/Tunis');
            $fmt->setPattern('d MMMM y');
            $formatted = $fmt->format($date);
            if (false !== $formatted && '' !== $formatted) {
                return $formatted;
            }
        }

        $months = [1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre'];
        $j = (int) $date->format('j');
        $n = (int) $date->format('n');
        $y = (int) $date->format('Y');

        return $j.' '.($months[$n] ?? $date->format('m')).' '.$y;
    }

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(
        EmployerContext $employerContext,
        JobOfferRepository $jobOfferRepository,
        ApplicationsTableGateway $applicationsTableGateway,
        UserRepository $userRepository,
    ): Response {
        $principal = $this->getUser();
        if (!$principal instanceof User || null === $principal->getId()) {
            throw $this->createAccessDeniedException('Compte employeur invalide.');
        }
        $user = $userRepository->find($principal->getId()) ?? $principal;
        $company = $employerContext->getCompanyForEmployer($user);

        $openCount = 0;
        $totalCount = 0;
        $recentOffers = [];
        $applicationsCount = 0;
        $recentCandidatures = [];

        $uid = $user->getId();
        if ($company !== null) {
            $openCount = $jobOfferRepository->countOpenByCompany($company);
            $all = $jobOfferRepository->findByCompanyOrdered($company);
            $totalCount = \count($all);
            $recentOffers = \array_slice($all, 0, 5);
        }

        if ($uid !== null) {
            $applicationsCount = $applicationsTableGateway->countByEmployerOwnerUserId((int) $uid);
            $allC = $applicationsTableGateway->fetchEmployerCandidatureListForDisplay((int) $uid);
            $recentCandidatures = \array_slice($allC, 0, 6);
        }

        return $this->render('recrutement/employer/dashboard/index.html.twig', [
            'company' => $company,
            'open_job_count' => $openCount,
            'total_job_count' => $totalCount,
            'recent_job_offers' => $recentOffers,
            'applications_count' => $applicationsCount,
            'recent_candidatures' => $recentCandidatures,
        ]);
    }

    #[Route('/finance', name: 'finance', methods: ['GET'])]
    public function finance(
        Request $request,
        UserRepository $userRepository,
        FinanceAnalyticsService $analyticsService,
        PayslipPayrollCalculator $payslipPayrollCalculator,
    ): Response {
        $principal = $this->getUser();
        if (!$principal instanceof User || null === $principal->getId()) {
            throw $this->createAccessDeniedException('Compte employeur invalide.');
        }
        $user = $userRepository->find($principal->getId()) ?? $principal;

        $portal = $analyticsService->getEmployerDashboardPayload();

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Africa/Tunis'));
        $hour = (int) $now->format('G');
        $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon apres-midi' : 'Bonsoir');
        $dateLabel = $this->formatFrenchDateForEmployerDashboard($now);

        $grossInput = (string) $request->query->get('gross', '');
        $taxEstimate = null;
        if ($grossInput !== '' && is_numeric($grossInput)) {
            $taxEstimate = $analyticsService->calculateTaxes((float) $grossInput);
        }

        $simpGrossStr = (string) $request->query->get('simp_gross', '');
        $simpCurrency = (string) $request->query->get('simp_currency', 'TND');
        $simpTax = null;
        if ($simpGrossStr !== '' && is_numeric($simpGrossStr)) {
            $simpTax = $payslipPayrollCalculator->computeFromGross((float) $simpGrossStr, 0.0);
        }

        return $this->render('recrutement/employer/finance/index.html.twig', [
            'page_title' => 'Finance',
            'employer_portal_json' => json_encode($portal, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
            'employer_greeting' => $greeting,
            'employer_date_label' => $dateLabel,
            'employer_user_display' => (string) ($user->getFullName() ?: $user->getUsername()),
            'tax_estimate' => $taxEstimate,
            'gross_input' => $grossInput,
            'simp_gross_input' => $simpGrossStr,
            'simp_currency' => $simpCurrency,
            'simp_tax' => $simpTax,
            'employer_pdf_export_url' => $this->generateUrl('app_employer_finance_export_my_report_pdf'),
        ]);
    }

    #[Route('/finance/export/my-report.pdf', name: 'finance_export_my_report_pdf', methods: ['GET'])]
    public function exportEmployerFinancePdf(Request $request, FinanceAnalyticsService $analyticsService, FinancePdfExportService $pdfExportService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw $this->createAccessDeniedException('Compte employeur invalide.');
        }

        $portal = $analyticsService->getEmployerDashboardPayload();
        $allowedIds = array_values(array_unique(array_map(static fn (array $m): int => (int) $m['userId'], $portal['members'])));
        if ($allowedIds === []) {
            throw $this->createNotFoundException('Aucun employe disponible pour l\'export.');
        }

        $requestedId = $request->query->getInt('user', 0);
        $employerId = (int) $user->getId();
        if ($requestedId > 0) {
            if (!\in_array($requestedId, $allowedIds, true)) {
                throw $this->createAccessDeniedException('Cet employe ne fait pas partie de votre equipe finance.');
            }
            $targetId = $requestedId;
        } else {
            $targetId = \in_array($employerId, $allowedIds, true) ? $employerId : $allowedIds[0];
        }

        $reportData = $analyticsService->getEmployeeReportData($targetId);
        if (null === $reportData) {
            throw $this->createNotFoundException('Aucune donnee finance trouvee pour cet employe.');
        }

        $pdf = $pdfExportService->buildEmployeeReportPdf($reportData);
        $safeName = preg_replace('/[^a-z0-9\-]+/i', '_', (string) $reportData['employee']['full_name']) ?? 'employee';

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="rapport_finance_%s.pdf"', strtolower($safeName)),
        ]);
    }
}

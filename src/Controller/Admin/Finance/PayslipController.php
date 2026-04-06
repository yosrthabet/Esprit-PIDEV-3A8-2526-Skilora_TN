<?php

namespace App\Controller\Admin\Finance;

use App\Entity\Finance\Payslip;
use App\Form\Finance\PayslipType;
use App\Repository\Finance\PayslipRepository;
use App\Service\Finance\PayslipPaymentSmsService;
use App\Service\FinancePdfExportService;
use App\Service\PayslipPayrollCalculator;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/finance/payslips')]
#[IsGranted('ROLE_ADMIN')]
final class PayslipController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PayslipRepository $payslipRepository,
    ) {
    }

    #[Route('', name: 'admin_finance_payslip_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = (string) $request->query->get('q', '');
        $payslips = $q !== ''
            ? $this->payslipRepository->searchByEmployeeName($q)
            : $this->payslipRepository->findAllOrdered();

        return $this->render('admin/finance/payslip/index.html.twig', [
            'page_title' => 'Bulletins de paie',
            'payslips' => $payslips,
            'search_query' => $q,
        ]);
    }

    #[Route('/new', name: 'admin_finance_payslip_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $payslip = new Payslip();
        $payslip->setMonth((int) date('n'));
        $payslip->setYear((int) date('Y'));
        $payslip->setCurrency('TND');
        $payslip->setStatus('DRAFT');
        $payslip->setBonuses(0.0);
        $payslip->setOvertimeHours(0.0);
        $payslip->setOvertimeTotal(0.0);
        $payslip->setOtherDeductions(0.0);
        $payslip->setDeductionsJson('[]');
        $payslip->setBonusesJson('[]');
        $form = $this->createForm(PayslipType::class, $payslip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($payslip);
            $this->entityManager->flush();
            $this->addFlash('success', 'Payslip created.');

            return $this->redirectToRoute('admin_finance_payslip_show', ['id' => $payslip->getId()]);
        }

        return $this->render('admin/finance/payslip/form.html.twig', [
            'page_title' => 'Générer un bulletin de paie',
            'form' => $form,
            'is_edit' => false,
            'calc_preview_url' => $this->generateUrl('admin_finance_payslip_calc_preview'),
        ]);
    }

    /**
     * Prévisualisation des montants (même logique que JavaFX : CNSS 9,18 %, IRPP 26 % après CNSS).
     */
    #[Route('/calc-preview', name: 'admin_finance_payslip_calc_preview', methods: ['POST'])]
    public function calcPreview(Request $request, PayslipPayrollCalculator $calculator): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $out = $calculator->computeFromComponents(
            (float) ($payload['baseSalary'] ?? 0),
            (float) ($payload['overtimeHours'] ?? 0),
            (float) ($payload['overtimeRate'] ?? 0),
            (float) ($payload['bonuses'] ?? 0),
            (float) ($payload['otherDeductions'] ?? 0),
        );

        return $this->json($out);
    }

    #[Route('/{id}/pay-and-notify', name: 'admin_finance_payslip_pay_notify', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function payAndNotify(Request $request, Payslip $payslip, PayslipPaymentSmsService $paymentSms): Response
    {
        if (!$this->isCsrfTokenValid('pay_notify'.$payslip->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_finance_payslip_show', ['id' => $payslip->getId()]);
        }

        $phone = trim((string) $request->request->get('phone', ''));
        $result = $paymentSms->payAndNotify($payslip, $phone);

        foreach ($result->messages as $msg) {
            $this->addFlash($result->success ? 'success' : 'error', $msg);
        }

        if ($result->success) {
            $payslip->setStatus('PAID');
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('admin_finance_payslip_show', ['id' => $payslip->getId()]);
    }

    #[Route('/{id}', name: 'admin_finance_payslip_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Payslip $payslip): Response
    {
        return $this->render('admin/finance/payslip/show.html.twig', [
            'page_title' => 'Bulletin #'.$payslip->getId(),
            'payslip' => $payslip,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_finance_payslip_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Payslip $payslip): Response
    {
        if ($payslip->getDeductionsJson() === null || trim((string) $payslip->getDeductionsJson()) === '') {
            $payslip->setDeductionsJson('[]');
        }
        if ($payslip->getBonusesJson() === null || trim((string) $payslip->getBonusesJson()) === '') {
            $payslip->setBonusesJson('[]');
        }
        if ($payslip->getOvertimeHours() === null) {
            $payslip->setOvertimeHours(0.0);
        }
        if ($payslip->getOvertimeTotal() === null) {
            $payslip->setOvertimeTotal(0.0);
        }
        if ($payslip->getBonuses() === null) {
            $payslip->setBonuses(0.0);
        }
        if ($payslip->getOtherDeductions() === null) {
            $payslip->setOtherDeductions(0.0);
        }

        $form = $this->createForm(PayslipType::class, $payslip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Payslip updated.');

            return $this->redirectToRoute('admin_finance_payslip_show', ['id' => $payslip->getId()]);
        }

        return $this->render('admin/finance/payslip/form.html.twig', [
            'page_title' => 'Modifier le bulletin #'.$payslip->getId(),
            'form' => $form,
            'is_edit' => true,
            'payslip' => $payslip,
            'calc_preview_url' => $this->generateUrl('admin_finance_payslip_calc_preview'),
        ]);
    }

    #[Route('/{id}/export.pdf', name: 'admin_finance_payslip_export_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportPdf(Payslip $payslip, FinancePdfExportService $pdfExportService, PayslipPayrollCalculator $calculator): Response
    {
        $pdf = $pdfExportService->buildPayslipPdf($payslip, $calculator);
        $safe = 'bulletin_'.$payslip->getId().'_'.sprintf('%02d', $payslip->getMonth() ?? 0).'_'.($payslip->getYear() ?? '');

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$safe.'.pdf"',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_finance_payslip_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Payslip $payslip): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$payslip->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('admin_finance_payslip_index');
        }

        try {
            $this->entityManager->remove($payslip);
            $this->entityManager->flush();
            $this->addFlash('success', 'Payslip deleted.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Cannot delete this payslip: it is referenced by other records.');
        }

        return $this->redirectToRoute('admin_finance_payslip_index');
    }
}

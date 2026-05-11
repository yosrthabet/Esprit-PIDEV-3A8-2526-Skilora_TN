<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Entity\User;
use App\Enum\Currency;
use App\Finance\Entity\ExchangeRate;
use App\Finance\Entity\Payslip;
use App\Finance\Repository\ExchangeRateRepository;
use App\Finance\Repository\PayslipRepository;
use App\Finance\Service\PayslipPdfService;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class FinanceAdminCatalogController extends AppController
{
    public function __construct(
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly PayslipRepository $payslipRepository,
        private readonly PayslipPdfService $payslipPdfService,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/admin/finance/exchange-rates', name: 'app_admin_finance_exchange_rates', methods: ['GET', 'POST'])]
    public function exchangeRates(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('exchange_rate_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $rate = (new ExchangeRate())
                ->setBaseCurrency(Currency::tryFrom($request->request->getString('base_currency')) ?? Currency::TND)
                ->setQuoteCurrency(Currency::tryFrom($request->request->getString('quote_currency')) ?? Currency::EUR)
                ->setRate($request->request->getString('rate') ?: '1.000000');
            $this->entityManager->persist($rate);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_admin_finance_exchange_rates');
        }

        return $this->render('finance/admin/exchange_rates.html.twig', [
            'rates' => $this->exchangeRateRepository->findBy([], ['effectiveAt' => 'DESC'], 50),
            'currencies' => Currency::cases(),
        ]);
    }

    #[Route('/admin/finance/payslips', name: 'app_admin_finance_payslips', methods: ['GET', 'POST'])]
    public function payslips(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('payslip_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $employee = $this->userRepository->find($request->request->getInt('employee_id'));
            if (!$employee instanceof User) {
                throw $this->createNotFoundException('Employee not found.');
            }
            $payslip = (new Payslip())
                ->setEmployee($employee)
                ->setPeriodStart(new \DateTimeImmutable($request->request->getString('period_start') ?: 'first day of this month'))
                ->setPeriodEnd(new \DateTimeImmutable($request->request->getString('period_end') ?: 'last day of this month'))
                ->setGrossAmount($request->request->getString('gross_amount') ?: '0.00')
                ->setNetAmount($request->request->getString('net_amount') ?: '0.00')
                ->setCurrency(Currency::tryFrom($request->request->getString('currency')) ?? Currency::TND);
            $this->entityManager->persist($payslip);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_admin_finance_payslips');
        }

        return $this->render('finance/admin/payslips.html.twig', [
            'payslips' => $this->payslipRepository->findBy([], ['periodEnd' => 'DESC'], 50),
            'users' => $this->userRepository->findAllOrderedByName(),
            'currencies' => Currency::cases(),
        ]);
    }

    #[Route('/admin/finance/payslips/{id}/pdf', name: 'app_admin_finance_payslip_pdf', methods: ['GET'])]
    public function payslipPdf(int $id): Response
    {
        $payslip = $this->payslipRepository->find($id);
        if (!$payslip instanceof Payslip) {
            throw $this->createNotFoundException('Payslip not found.');
        }

        $pdf = $this->payslipPdfService->generate($payslip);
        $filename = sprintf('payslip_%s_%s.pdf', $payslip->getEmployee()->getId(), $payslip->getPeriodEnd()->format('Y-m'));

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}

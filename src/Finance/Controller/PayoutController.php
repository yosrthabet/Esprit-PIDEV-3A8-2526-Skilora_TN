<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Entity\PayoutRequest;
use App\Finance\Repository\BankAccountRepository;
use App\Finance\Repository\PayoutRequestRepository;
use App\Finance\Service\PayoutService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PayoutController extends AppController
{
    public function __construct(
        private readonly PayoutRequestRepository $payoutRepository,
        private readonly BankAccountRepository $bankAccountRepository,
        private readonly PayoutService $payoutService,
    ) {
    }

    #[Route('/finance/payouts', name: 'app_finance_payouts', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getAppUser();

        return $this->render('finance/payouts/index.html.twig', [
            'payouts' => $this->payoutRepository->findForUser($user),
            'bank_accounts' => $this->bankAccountRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/finance/payouts/request', name: 'app_finance_payout_request', methods: ['POST'])]
    public function request(Request $request): Response
    {
        $user = $this->getAppUser();

        if (!$this->isCsrfTokenValid('payout_request', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_finance_payouts');
        }

        $bankAccountId = $request->request->getInt('bank_account_id');
        $amount = $request->request->getString('amount', '0');
        $note = $request->request->getString('note') ?: null;

        $bankAccount = $this->bankAccountRepository->find($bankAccountId);
        if ($bankAccount === null || $bankAccount->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Invalid bank account.');
            return $this->redirectToRoute('app_finance_payouts');
        }

        try {
            $this->payoutService->requestPayout($user, $bankAccount, $amount, $note);
            $this->addFlash('success', 'Payout request submitted successfully.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_finance_payouts');
    }

    #[Route('/admin/finance/payouts', name: 'app_admin_finance_payouts', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        return $this->render('finance/admin/payouts.html.twig', [
            'payouts' => $this->payoutRepository->findAll(),
        ]);
    }

    #[Route('/admin/finance/payouts/{id}/approve', name: 'app_admin_finance_payout_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Request $request, PayoutRequest $payout): Response
    {
        if (!$this->isCsrfTokenValid('payout_action_' . $payout->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_finance_payouts');
        }

        try {
            $this->payoutService->approve($payout, $request->request->getString('admin_note') ?: null);
            $this->addFlash('success', 'Payout #' . $payout->getId() . ' approved.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_finance_payouts');
    }

    #[Route('/admin/finance/payouts/{id}/reject', name: 'app_admin_finance_payout_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Request $request, PayoutRequest $payout): Response
    {
        if (!$this->isCsrfTokenValid('payout_action_' . $payout->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_finance_payouts');
        }

        try {
            $this->payoutService->reject($payout, $request->request->getString('admin_note') ?: null);
            $this->addFlash('success', 'Payout #' . $payout->getId() . ' rejected. Funds returned to wallet.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_finance_payouts');
    }
}

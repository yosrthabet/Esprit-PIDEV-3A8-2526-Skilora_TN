<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Enum\Currency;
use App\Finance\Entity\PaymentTransaction;
use App\Finance\Entity\Wallet;
use App\Finance\Repository\ExchangeRateRepository;
use App\Finance\Repository\PaymentTransactionRepository;
use App\Finance\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class WalletController extends AppController
{
    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly PaymentTransactionRepository $transactionRepository,
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/finance/wallet', name: 'app_finance_wallet', methods: ['GET'])]
    #[Route('/workspace/wallet', name: 'app_workspace_wallet', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getAppUser();
        $currency = $user->getPreferredCurrency();
        $wallet = $this->walletRepository->findOneForUser($user, $currency) ?? $this->createWallet($currency);

        $rate = $this->exchangeRateRepository->findLatestRate(Currency::EUR, $currency);

        return $this->render('finance/wallet/index.html.twig', [
            'wallet' => $wallet,
            'transactions' => $this->transactionRepository->findForUser($user),
            'currencies' => Currency::cases(),
            'currentCurrency' => $currency,
            'eurRate' => $rate?->getRate() ?? '—',
        ]);
    }

    #[Route('/finance/wallet/currency', name: 'app_finance_wallet_currency', methods: ['POST'])]
    public function switchCurrency(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('wallet_currency', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $newCurrency = Currency::tryFrom($request->request->getString('currency'));
        if ($newCurrency === null) {
            $this->addFlash('error', 'Invalid currency.');
            return $this->redirectToRoute('app_finance_wallet');
        }

        $user->setPreferredCurrency($newCurrency);
        $this->entityManager->flush();

        $existing = $this->walletRepository->findOneForUser($user, $newCurrency);
        if ($existing === null) {
            $this->createWallet($newCurrency);
        }

        $this->addFlash('success', 'Wallet currency set to ' . $newCurrency->value . '.');
        return $this->redirectToRoute('app_finance_wallet');
    }

    #[Route('/finance/wallet/recharge', name: 'app_finance_wallet_recharge', methods: ['POST'])]
    #[Route('/workspace/wallet/recharge', name: 'app_workspace_wallet_recharge', methods: ['POST'])]
    public function recharge(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('wallet_recharge_' . $this->getAppUser()->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $amount = number_format(max(0, (float) $request->request->getString('amount')), 2, '.', '');
        if (bccomp($amount, '0.00', 2) <= 0) {
            $this->addFlash('error', 'Amount must be greater than zero.');
            return $this->redirectToRoute('app_finance_wallet');
        }

        $user = $this->getAppUser();
        $currency = $user->getPreferredCurrency();
        $wallet = $this->walletRepository->findOneForUser($user, $currency) ?? $this->createWallet($currency, false);
        $wallet->credit($amount);
        $transaction = (new PaymentTransaction())
            ->setUser($user)
            ->setType('manual_topup')
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setProviderReference('manual-' . bin2hex(random_bytes(4)));
        $this->entityManager->persist($wallet);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
        $this->addFlash('success', 'Wallet credited with ' . $amount . ' ' . $currency->value . '.');

        return $this->redirectToRoute('app_finance_wallet');
    }

    private function createWallet(Currency $currency, bool $flush = true): Wallet
    {
        $wallet = (new Wallet())->setUser($this->getAppUser())->setCurrency($currency);
        $this->entityManager->persist($wallet);
        if ($flush) {
            $this->entityManager->flush();
        }

        return $wallet;
    }
}

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
use App\Service\Integration\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class StripeController extends AppController
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly WalletRepository $walletRepository,
        private readonly PaymentTransactionRepository $transactionRepository,
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/wallet/topup/checkout', name: 'app_wallet_topup_checkout', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function checkout(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('wallet_topup', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $user = $this->getAppUser();
        $rawAmount = max(1, (int) $request->request->getInt('amount'));
        $amountCents = $rawAmount * 100;
        $session = $this->stripeService->createCheckoutSession([
            'amount' => $amountCents,
            'currency' => 'eur',
            'description' => 'Skilora Wallet Top-up — ' . $rawAmount . ' EUR',
            'success_url' => $this->generateUrl('app_wallet_topup_success', [], 0),
            'cancel_url' => $this->generateUrl('app_finance_wallet', [], 0),
            'metadata' => [
                'user_id' => (string) $user->getId(),
                'amount' => number_format($rawAmount, 2, '.', ''),
                'type' => 'wallet_topup',
            ],
        ]);

        return $this->redirect($session['url']);
    }

    #[Route('/wallet/topup/success', name: 'app_wallet_topup_success', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function success(Request $request): Response
    {
        $sessionId = $request->query->getString('session_id');
        if ($sessionId !== '' && !str_starts_with($sessionId, 'cs_mock_')) {
            $user = $this->getAppUser();
            $wallet = $this->walletRepository->findOneForUser($user);
            if ($wallet !== null) {
                $this->addFlash('success', 'Payment received! Your wallet has been credited.');
            } else {
                $this->addFlash('success', 'Payment received! Wallet will be credited shortly.');
            }
        } else {
            $this->addFlash('success', 'Wallet top-up successful!');
        }

        return $this->redirectToRoute('app_finance_wallet');
    }

    #[Route('/webhook/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature', '');

        if (!$this->stripeService->verifyWebhookSignature($payload, $signature)) {
            return new JsonResponse(['error' => 'Invalid signature'], 400);
        }

        /** @var array{type?: string, data?: array{object?: array{metadata?: array{user_id?: string, amount?: string}}}} $event */
        $event = json_decode($payload, true) ?: [];
        $type = $event['type'] ?? '';

        if ($type === 'checkout.session.completed') {
            $meta = $event['data']['object']['metadata'] ?? [];
            $userId = (int) ($meta['user_id'] ?? 0);
            $amount = $meta['amount'] ?? '0';
            $sessionId = $event['data']['object']['id'] ?? '';

            if ($userId > 0 && (float) $amount > 0) {
                $existing = is_string($sessionId) && $sessionId !== ''
                    ? $this->transactionRepository->findOneBy(['providerReference' => $sessionId])
                    : null;
                if ($existing !== null) {
                    return new JsonResponse(['status' => 'already_processed']);
                }

                $userRepo = $this->entityManager->getRepository(\App\Entity\User::class);
                $user = $userRepo->find($userId);
                if ($user !== null) {
                    $walletCurrency = $user->getPreferredCurrency();
                    $convertedAmount = $this->exchangeRateRepository->convert($amount, Currency::EUR, $walletCurrency);

                    $wallet = $this->walletRepository->findOneForUser($user, $walletCurrency);
                    if ($wallet === null) {
                        $wallet = (new Wallet())->setUser($user)->setCurrency($walletCurrency);
                        $this->entityManager->persist($wallet);
                    }
                    $wallet->credit($convertedAmount);

                    $transaction = (new PaymentTransaction())
                        ->setUser($user)
                        ->setType('stripe_topup')
                        ->setAmount($convertedAmount)
                        ->setCurrency($walletCurrency)
                        ->setProviderReference(is_string($sessionId) ? $sessionId : '');
                    $this->entityManager->persist($transaction);
                    $this->entityManager->flush();
                }
            }
        }

        return new JsonResponse(['status' => 'ok']);
    }
}

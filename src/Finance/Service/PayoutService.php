<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\User;
use App\Enum\PayoutStatus;
use App\Finance\Entity\BankAccount;
use App\Finance\Entity\PayoutRequest;
use App\Finance\Entity\Wallet;
use App\Finance\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;

class PayoutService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WalletRepository $walletRepository,
    ) {
    }

    public function requestPayout(User $user, BankAccount $bankAccount, string $amount, ?string $note = null): PayoutRequest
    {
        $wallet = $this->walletRepository->findOneForUser($user);

        if ($wallet === null) {
            throw new \DomainException('No wallet found for this user.');
        }

        $amountFloat = (float) $amount;
        if ($amountFloat <= 0) {
            throw new \DomainException('Payout amount must be positive.');
        }

        if ((float) $wallet->getBalance() < $amountFloat) {
            throw new \DomainException('Insufficient wallet balance.');
        }

        $payout = new PayoutRequest();
        $payout->setUser($user)
            ->setBankAccount($bankAccount)
            ->setAmount($amount)
            ->setCurrency($wallet->getCurrency())
            ->setNote($note);

        $wallet->debit($amount);

        $this->em->persist($payout);
        $this->em->flush();

        return $payout;
    }

    public function approve(PayoutRequest $payout, ?string $adminNote = null): void
    {
        if (!$payout->isPending()) {
            throw new \DomainException('Only pending payouts can be approved.');
        }

        $payout->setStatus(PayoutStatus::APPROVED)
            ->setAdminNote($adminNote)
            ->markProcessed();

        $this->em->flush();
    }

    public function reject(PayoutRequest $payout, ?string $adminNote = null): void
    {
        if (!$payout->isPending()) {
            throw new \DomainException('Only pending payouts can be rejected.');
        }

        $wallet = $this->walletRepository->findOneForUser($payout->getUser());
        if ($wallet instanceof Wallet) {
            $wallet->credit($payout->getAmount());
        }

        $payout->setStatus(PayoutStatus::REJECTED)
            ->setAdminNote($adminNote)
            ->markProcessed();

        $this->em->flush();
    }
}

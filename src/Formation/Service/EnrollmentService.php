<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Entity\User;
use App\Enum\Currency;
use App\Finance\Entity\PaymentTransaction;
use App\Finance\Entity\Wallet;
use App\Finance\Repository\WalletRepository;
use App\Formation\Entity\Enrollment;
use App\Formation\Entity\Formation;
use App\Formation\EnrollmentStatus;
use App\Formation\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;

class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly WalletRepository $walletRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function enroll(User $user, Formation $formation): Enrollment
    {
        $existing = $this->enrollmentRepository->findOneForUserAndFormation($user, $formation);
        if ($existing !== null) {
            return $existing;
        }

        if (!$formation->isPublished()) {
            throw new \RuntimeException('Only published formations can be enrolled.');
        }

        $price = (float) ($formation->getPriceAmount() ?? '0');

        if ($price > 0) {
            $wallet = $this->walletRepository->findOneForUser($user);
            if ($wallet === null || (float) $wallet->getBalance() < $price) {
                $balance = $wallet !== null ? $wallet->getBalance() : '0.00';
                throw new \RuntimeException(
                    sprintf('Insufficient wallet balance. Required: %.2f TND, available: %s TND.', $price, $balance)
                );
            }

            $formattedPrice = number_format($price, 2, '.', '');
            $wallet->debit($formattedPrice);

            $transaction = (new PaymentTransaction())
                ->setUser($user)
                ->setType('formation_enrollment')
                ->setAmount($formattedPrice)
                ->setCurrency(Currency::TND)
                ->setProviderReference('formation-' . $formation->getId());
            $this->entityManager->persist($transaction);

            $this->creditTrainerWallet($formation->getTrainer(), $formattedPrice, $formation);
        }

        $enrollment = (new Enrollment())
            ->setUser($user)
            ->setFormation($formation);

        $this->entityManager->persist($enrollment);
        $this->entityManager->flush();

        return $enrollment;
    }

    private function creditTrainerWallet(User $trainer, string $amount, Formation $formation): void
    {
        $wallet = $this->walletRepository->findOneForUser($trainer, Currency::TND);
        if ($wallet === null) {
            $wallet = (new Wallet())
                ->setUser($trainer)
                ->setCurrency(Currency::TND);
            $this->entityManager->persist($wallet);
        }
        $wallet->credit($amount);

        $tx = (new PaymentTransaction())
            ->setUser($trainer)
            ->setType('formation_income')
            ->setAmount($amount)
            ->setCurrency(Currency::TND)
            ->setProviderReference('formation-income-' . $formation->getId());
        $this->entityManager->persist($tx);
    }

    public function cancel(Enrollment $enrollment): void
    {
        if ($enrollment->getStatus() === EnrollmentStatus::COMPLETED) {
            throw new \RuntimeException('Completed enrollments cannot be cancelled.');
        }

        $enrollment->cancel();
        $this->entityManager->flush();
    }
}

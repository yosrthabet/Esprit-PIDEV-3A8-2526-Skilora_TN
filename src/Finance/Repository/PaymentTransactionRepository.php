<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Finance\Entity\PaymentTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PaymentTransaction> */
class PaymentTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, PaymentTransaction::class); }

    /** @return list<PaymentTransaction> */
    public function findForUser(User $user, int $limit = 50): array
    {
        /** @var list<PaymentTransaction> $result */
        $result = $this->findBy(['user' => $user], ['createdAt' => 'DESC'], $limit);
        return $result;
    }

    public function sumSucceededForUser(User $user): float
    {
        $total = 0.0;
        foreach ($this->findForUser($user, 500) as $transaction) {
            if ($transaction->getStatus() === 'succeeded') {
                $total += (float) $transaction->getAmount();
            }
        }

        return $total;
    }
}

<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Enum\PayoutStatus;
use App\Finance\Entity\PayoutRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PayoutRequest>
 */
class PayoutRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayoutRequest::class);
    }

    /** @return list<PayoutRequest> */
    public function findForUser(User $user, int $limit = 20): array
    {
        /** @var list<PayoutRequest> $payouts */
        $payouts = $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $payouts;
    }

    /** @return list<PayoutRequest> */
    public function findPending(): array
    {
        /** @var list<PayoutRequest> $payouts */
        $payouts = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', PayoutStatus::PENDING)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $payouts;
    }

    /** @return list<PayoutRequest> */
    public function findAll(): array
    {
        /** @var list<PayoutRequest> $payouts */
        $payouts = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $payouts;
    }
}

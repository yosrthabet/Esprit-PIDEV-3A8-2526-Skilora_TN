<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Finance\Entity\EscrowTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EscrowTransaction> */
class EscrowTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, EscrowTransaction::class); }

    /** @return list<EscrowTransaction> */
    public function findForUser(User $user, ?int $limit = null): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->join('t.contract', 'c')->addSelect('c')
            ->join('c.hireOffer', 'h')->addSelect('h')
            ->join('h.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->leftJoin('j.company', 'co')->addSelect('co')
            ->where('a.candidate = :user OR co.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        /** @var list<EscrowTransaction> $transactions */
        $transactions = $queryBuilder
            ->getQuery()
            ->getResult();

        return $transactions;
    }
}

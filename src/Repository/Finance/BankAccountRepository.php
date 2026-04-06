<?php

namespace App\Repository\Finance;

use App\Entity\Finance\BankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BankAccount>
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    /**
     * @return \App\Entity\Finance\BankAccount[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.user', 'u')->addSelect('u')
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \App\Entity\Finance\BankAccount[]
     */
    public function searchByEmployeeName(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return $this->findAllOrdered();
        }

        return $this->createQueryBuilder('b')
            ->leftJoin('b.user', 'u')->addSelect('u')
            ->where('u.username LIKE :q OR u.fullName LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%'.$q.'%')
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

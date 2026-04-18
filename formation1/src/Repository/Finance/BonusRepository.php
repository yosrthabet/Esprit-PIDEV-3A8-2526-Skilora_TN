<?php

namespace App\Repository\Finance;

use App\Entity\Finance\Bonus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bonus>
 */
class BonusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bonus::class);
    }

    /**
     * @return Bonus[]
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
     * @return Bonus[]
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

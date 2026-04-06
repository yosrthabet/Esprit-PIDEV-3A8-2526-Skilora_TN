<?php

namespace App\Repository\Finance;

use App\Entity\Finance\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    /**
     * @return Contract[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->leftJoin('c.company', 'co')->addSelect('co')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Contract[]
     */
    public function searchByEmployeeName(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return $this->findAllOrdered();
        }

        return $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->leftJoin('c.company', 'co')->addSelect('co')
            ->where('u.username LIKE :q OR u.fullName LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%'.$q.'%')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

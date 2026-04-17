<?php

namespace App\Repository\Finance;

use App\Entity\Finance\Payslip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payslip>
 */
class PayslipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payslip::class);
    }

    /**
     * @return Payslip[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->orderBy('p.year', 'DESC')
            ->addOrderBy('p.month', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Payslip[]
     */
    public function searchByEmployeeName(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return $this->findAllOrdered();
        }

        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->where('u.username LIKE :q OR u.fullName LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%'.$q.'%')
            ->orderBy('p.year', 'DESC')
            ->addOrderBy('p.month', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

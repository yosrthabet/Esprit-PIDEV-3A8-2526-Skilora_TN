<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Finance\Entity\Contract;
use App\Recruitment\Entity\HireOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Contract> */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    public function findOneByHireOffer(HireOffer $hireOffer): ?Contract
    {
        return $this->findOneBy(['hireOffer' => $hireOffer]);
    }

    /** @return list<Contract> */
    public function findForUser(User $user): array
    {
        /** @var list<Contract> $contracts */
        $contracts = $this->createQueryBuilder('c')
            ->join('c.hireOffer', 'h')->addSelect('h')
            ->join('h.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->leftJoin('j.company', 'co')->addSelect('co')
            ->where('a.candidate = :user OR co.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $contracts;
    }

    /** @return list<Contract> */
    public function findRecent(int $limit = 80): array
    {
        /** @var list<Contract> $contracts */
        $contracts = $this->createQueryBuilder('c')
            ->join('c.hireOffer', 'h')->addSelect('h')
            ->join('h.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->leftJoin('j.company', 'co')->addSelect('co')
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $contracts;
    }
}

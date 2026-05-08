<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Enum\DisputeStatus;
use App\Finance\Entity\ContractDispute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ContractDispute> */
class ContractDisputeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ContractDispute::class); }

    /** @return list<ContractDispute> */
    public function findOpen(): array
    {
        /** @var list<ContractDispute> $disputes */
        $disputes = $this->createQueryBuilder('d')
            ->join('d.contract', 'c')->addSelect('c')
            ->where('d.status = :status')
            ->setParameter('status', DisputeStatus::OPEN)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $disputes;
    }
}

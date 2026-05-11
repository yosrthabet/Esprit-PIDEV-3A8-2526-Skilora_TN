<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Finance\Entity\Contract;
use App\Finance\Entity\ContractMilestone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ContractMilestone> */
class ContractMilestoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ContractMilestone::class); }
    /** @return list<ContractMilestone> */
    public function findForContract(Contract $contract): array { /** @var list<ContractMilestone> $r */ $r = $this->findBy(['contract' => $contract], ['createdAt' => 'DESC']); return $r; }
}

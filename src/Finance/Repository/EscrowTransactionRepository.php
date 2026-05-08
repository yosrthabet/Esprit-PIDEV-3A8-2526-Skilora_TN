<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Finance\Entity\EscrowTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EscrowTransaction> */
class EscrowTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, EscrowTransaction::class); }
}

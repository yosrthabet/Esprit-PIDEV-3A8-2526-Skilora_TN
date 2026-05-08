<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Finance\Entity\ContractDelivery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ContractDelivery> */
class ContractDeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ContractDelivery::class); }
}

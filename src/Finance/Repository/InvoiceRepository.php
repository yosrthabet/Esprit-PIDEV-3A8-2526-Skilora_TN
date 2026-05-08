<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Finance\Entity\Contract;
use App\Finance\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Invoice> */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Invoice::class); }

    public function findOneForContract(Contract $contract): ?Invoice
    {
        return $this->findOneBy(['contract' => $contract]);
    }
}

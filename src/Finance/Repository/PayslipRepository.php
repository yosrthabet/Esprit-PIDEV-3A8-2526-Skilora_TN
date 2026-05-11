<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Finance\Entity\Payslip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Payslip> */
class PayslipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Payslip::class); }
    /** @return list<Payslip> */
    public function findForUser(User $user): array { /** @var list<Payslip> $r */ $r = $this->findBy(['employee' => $user], ['periodEnd' => 'DESC']); return $r; }
}

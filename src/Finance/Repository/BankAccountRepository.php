<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Finance\Entity\BankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<BankAccount> */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, BankAccount::class); }
    /** @return list<BankAccount> */
    public function findForUser(User $user): array { /** @var list<BankAccount> $r */ $r = $this->findBy(['user' => $user], ['createdAt' => 'DESC']); return $r; }
}

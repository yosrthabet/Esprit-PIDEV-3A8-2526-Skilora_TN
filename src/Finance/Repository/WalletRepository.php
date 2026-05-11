<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Enum\Currency;
use App\Finance\Entity\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Wallet> */
class WalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Wallet::class); }

    public function findOneForUser(User $user, Currency $currency = Currency::TND): ?Wallet
    {
        return $this->findOneBy(['user' => $user, 'currency' => $currency]);
    }
}

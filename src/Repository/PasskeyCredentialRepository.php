<?php

namespace App\Repository;

use App\Entity\PasskeyCredential;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasskeyCredential>
 */
class PasskeyCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasskeyCredential::class);
    }

    public function findByCredentialId(string $credentialId): ?PasskeyCredential
    {
        return $this->findOneBy(['credentialId' => $credentialId]);
    }

    /** @return PasskeyCredential[] */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function hasPasskeys(User $user): bool
    {
        return $this->count(['user' => $user]) > 0;
    }
}

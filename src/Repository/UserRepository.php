<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function getRecentUsers(int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function countActiveAccounts(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.active = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countVerifiedAccounts(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.verified = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{name: string, email: string, role: string, status: string}>
     */
    public function findRecentSummaries(int $limit = 8): array
    {
        /** @var User[] $users */
        $users = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($users as $user) {
            $out[] = [
                'name' => $user->getDisplayName(),
                'email' => $user->getEmail() ?? $user->getUsername(),
                'role' => match (strtoupper(trim($user->getRole() ?? ''))) {
                    'ADMIN' => 'Administrateur',
                    'TRAINER' => 'Formateur',
                    'EMPLOYER' => 'Employeur',
                    default => 'Utilisateur',
                },
                'status' => !$user->isActive() ? 'Inactif' : ($user->isVerified() ? 'Actif' : 'En attente'),
            ];
        }

        return $out;
    }

    /**
     * @return User[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.fullName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

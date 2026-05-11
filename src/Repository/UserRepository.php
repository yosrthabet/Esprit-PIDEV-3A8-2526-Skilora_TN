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
        /** @var User[] $users */
        $users = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $users;
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
     * @return list<array{name: string, email: string|null, role: string, status: string}>
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
        /** @var User[] $users */
        $users = $this->createQueryBuilder('u')
            ->orderBy('u.fullName', 'ASC')
            ->getQuery()
            ->getResult();

        return $users;
    }

    /** @return list<User> */
    public function searchByName(string $query, int $limit = 10): array
    {
        /** @var list<User> $users */
        $users = $this->createQueryBuilder('u')
            ->where('u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.firstName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $users;
    }

    /** @return list<User> */
    public function findForAdmin(?string $query, ?string $role, ?string $status, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyAdminFilters($qb, $query, $role, $status);

        /** @var list<User> $users */
        $users = $qb->getQuery()->getResult();

        return $users;
    }

    public function countForAdmin(?string $query, ?string $role, ?string $status): int
    {
        $qb = $this->createQueryBuilder('u')->select('COUNT(u.id)');
        $this->applyAdminFilters($qb, $query, $role, $status);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countByRole(string $role): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('UPPER(u.role) = :role')
            ->setParameter('role', strtoupper($role))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function applyAdminFilters(\Doctrine\ORM\QueryBuilder $qb, ?string $query, ?string $role, ?string $status): void
    {
        if ($query !== null && trim($query) !== '') {
            $needle = '%' . mb_strtolower(trim($query)) . '%';
            $qb->andWhere('LOWER(u.username) LIKE :query OR LOWER(u.email) LIKE :query OR LOWER(u.fullName) LIKE :query')
                ->setParameter('query', $needle);
        }

        if ($role !== null && $role !== '' && $role !== 'all') {
            $qb->andWhere('UPPER(u.role) = :role')->setParameter('role', strtoupper($role));
        }

        if ($status === 'active') {
            $qb->andWhere('u.active = true');
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.active = false');
        } elseif ($status === 'verified') {
            $qb->andWhere('u.verified = true');
        } elseif ($status === 'pending') {
            $qb->andWhere('u.verified = false');
        }
    }
}

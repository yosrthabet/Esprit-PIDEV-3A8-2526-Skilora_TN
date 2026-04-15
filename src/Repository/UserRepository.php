<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $identifier = trim($identifier);

        return $this->createQueryBuilder('u')
            ->where('u.username = :id')
            ->orWhere('u.email = :id')
            ->setParameter('id', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
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

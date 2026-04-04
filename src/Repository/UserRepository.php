<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Loads users for login by email or username (whichever is stored in the database).
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface, PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneForLogin($identifier);
    }

    public function upgradePassword(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Unsupported user class');
        }

        $user->setPasswordHash($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    private function findOneForLogin(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if ('' === $identifier) {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->where('u.email = :id OR u.username = :id')
            ->setParameter('id', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findAllForListing(int $limit = 100): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Comptes considérés comme actifs ({@see User::isActive()} : true par défaut si NULL en base).
     */
    public function countActiveAccounts(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isActive = true OR u.isActive IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countVerifiedAccounts(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isVerified = true')
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
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($users as $user) {
            $out[] = [
                'name' => $user->getFullName(),
                'email' => $user->getEmail() ?? $user->getUsername(),
                'role' => $this->mapRoleLabel($user->getRole()),
                'status' => $this->mapAccountStatus($user),
            ];
        }

        return $out;
    }

    private function mapRoleLabel(string $role): string
    {
        return match (strtoupper(trim($role))) {
            'ADMIN' => 'Administrateur',
            default => 'Utilisateur',
        };
    }

    private function mapAccountStatus(User $user): string
    {
        if (!$user->isActive()) {
            return 'Inactif';
        }

        return $user->isVerified() ? 'Actif' : 'En attente';
    }
}

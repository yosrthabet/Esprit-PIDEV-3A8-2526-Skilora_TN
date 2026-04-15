<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Formation;
use App\Enum\FormationLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
final class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * @return Formation[]
     */
    public function findPaginated(int $page, int $limit, ?string $searchQuery): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC');

        $clean = $this->normalizeSearchQuery($searchQuery);
        if (null !== $clean) {
            $qb->andWhere('f.title LIKE :q OR f.description LIKE :q')
                ->setParameter('q', '%'.$clean.'%');
        }

        return $qb
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(?string $searchQuery): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)');

        $clean = $this->normalizeSearchQuery($searchQuery);
        if (null !== $clean) {
            $qb->andWhere('f.title LIKE :q OR f.description LIKE :q')
                ->setParameter('q', '%'.$clean.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByTrainerPaginated(int $trainerId, int $page, int $limit, ?string $searchQuery): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.createdBy = :tid')
            ->setParameter('tid', $trainerId)
            ->orderBy('f.createdAt', 'DESC');

        $clean = $this->normalizeSearchQuery($searchQuery);
        if (null !== $clean) {
            $qb->andWhere('f.title LIKE :q OR f.description LIKE :q')
                ->setParameter('q', '%'.$clean.'%');
        }

        return $qb
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByTrainer(int $trainerId, ?string $searchQuery = null): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.createdBy = :tid')
            ->setParameter('tid', $trainerId);

        $clean = $this->normalizeSearchQuery($searchQuery);
        if (null !== $clean) {
            $qb->andWhere('f.title LIKE :q OR f.description LIKE :q')
                ->setParameter('q', '%'.$clean.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Formation[]
     */
    public function findAllForCatalog(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Catalogue public avec recherche et filtres (GET).
     *
     * @return Formation[]
     */
    public function findCatalogFiltered(?string $searchQuery, ?string $category, ?string $level): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('f.title', 'ASC');

        $clean = $this->normalizeSearchQuery($searchQuery);
        if (null !== $clean) {
            $qb->andWhere('f.title LIKE :q OR f.description LIKE :q')
                ->setParameter('q', '%'.$clean.'%');
        }

        if (null !== $category && '' !== $category && \in_array($category, Formation::getCategoryKeys(), true)) {
            $qb->andWhere('f.category = :cat')->setParameter('cat', $category);
        }

        if (null !== $level && '' !== $level) {
            $lvl = FormationLevel::tryFrom($level);
            if (null !== $lvl) {
                $qb->andWhere('f.level = :lvl')->setParameter('lvl', $lvl);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Formation[]
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Duplicate title check (trim + case-insensitive). Excludes current entity on update.
     */
    public function findAnotherWithSameTitle(string $title, ?int $excludeId): ?Formation
    {
        $normalized = mb_strtolower(trim($title));

        $qb = $this->createQueryBuilder('f')
            ->where('LOWER(TRIM(f.title)) = :t')
            ->setParameter('t', $normalized)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb->andWhere('f.id != :id')->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Recherche sécurisée : retire les jokers SQL (% _) et limite la longueur côté requête.
     */
    private function normalizeSearchQuery(?string $searchQuery): ?string
    {
        if (null === $searchQuery || '' === trim($searchQuery)) {
            return null;
        }

        $trimmed = trim($searchQuery);
        $maxLen = 200;
        if (\function_exists('mb_substr')) {
            $trimmed = mb_substr($trimmed, 0, $maxLen);
        } else {
            $trimmed = substr($trimmed, 0, $maxLen);
        }

        $stripped = (string) preg_replace('/[%_\\\\]/u', '', $trimmed);
        $stripped = trim($stripped);

        return '' !== $stripped ? $stripped : null;
    }
}

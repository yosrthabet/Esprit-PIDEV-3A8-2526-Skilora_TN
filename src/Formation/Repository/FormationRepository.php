<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Entity\User;
use App\Formation\Entity\Formation;
use App\Formation\FormationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Formation> */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /** @return list<Formation> */
    public function findPublished(?string $category = null, int $limit = 20, ?string $query = null, ?\App\Formation\FormationLevel $level = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.status = :status')
            ->setParameter('status', FormationStatus::PUBLISHED)
            ->orderBy('f.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if ($category !== null && $category !== '') {
            $qb->andWhere('f.category = :category')->setParameter('category', $category);
        }

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(f.title) LIKE :query OR LOWER(f.description) LIKE :query OR LOWER(f.category) LIKE :query')
                ->setParameter('query', '%' . mb_strtolower($query) . '%');
        }

        if ($level !== null) {
            $qb->andWhere('f.level = :level')->setParameter('level', $level);
        }

        /** @var list<Formation> $formations */
        $formations = $qb->getQuery()->getResult();

        return $formations;
    }

    /** @return list<Formation> */
    public function findForTrainer(User $trainer, int $limit = 50): array
    {
        /** @var list<Formation> $formations */
        $formations = $this->createQueryBuilder('f')
            ->where('f.trainer = :trainer')
            ->setParameter('trainer', $trainer)
            ->orderBy('f.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $formations;
    }

    /** @return list<Formation> */
    public function findForTrainerPaginated(User $trainer, ?string $query, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.trainer = :trainer')
            ->setParameter('trainer', $trainer)
            ->orderBy('f.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(f.title) LIKE :query OR LOWER(f.category) LIKE :query')
                ->setParameter('query', '%' . mb_strtolower($query) . '%');
        }

        /** @var list<Formation> $formations */
        $formations = $qb->getQuery()->getResult();

        return $formations;
    }

    public function countForTrainer(User $trainer, ?string $query = null): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.trainer = :trainer')
            ->setParameter('trainer', $trainer);

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(f.title) LIKE :query OR LOWER(f.category) LIKE :query')
                ->setParameter('query', '%' . mb_strtolower($query) . '%');
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

    /** @return list<Formation> */
    public function findRecentForTrainer(User $trainer, int $limit = 5): array
    {
        return $this->findForTrainer($trainer, $limit);
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\FormationReview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Formation-scoped reviews (see {@see FormationReview}).
 *
 * @extends ServiceEntityRepository<FormationReview>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationReview::class);
    }

    public function getAverageRating(Formation $formation): float
    {
        $avg = $this->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->where('r.formation = :formation')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleScalarResult();

        return null !== $avg ? round((float) $avg, 2) : 0.0;
    }

    public function getTotalReviews(Formation $formation): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.formation = :formation')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Share of reviews with rating &gt;= 4 (recommended / positive).
     */
    public function getLikePercentage(Formation $formation): float
    {
        $total = $this->getTotalReviews($formation);
        if (0 === $total) {
            return 0.0;
        }

        $likes = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.formation = :formation')
            ->andWhere('r.rating >= 4')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleScalarResult();

        return round(100.0 * $likes / $total, 1);
    }

    /**
     * @return array{1: int, 2: int, 3: int, 4: int, 5: int}
     */
    public function getRatingDistribution(Formation $formation): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.rating AS rating, COUNT(r.id) AS total')
            ->where('r.formation = :formation')
            ->setParameter('formation', $formation)
            ->groupBy('r.rating')
            ->getQuery()
            ->getArrayResult();

        $result = array_fill(1, 5, 0);
        foreach ($rows as $row) {
            $result[(int) $row['rating']] = (int) $row['total'];
        }

        return $result;
    }

    public function findOneByFormationAndUser(Formation $formation, User $user): ?FormationReview
    {
        return $this->createQueryBuilder('r')
            ->where('r.formation = :formation')
            ->andWhere('r.user = :user')
            ->setParameter('formation', $formation)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<FormationReview>
     */
    public function findLatestByFormation(Formation $formation, int $limit = 12): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.formation = :formation')
            ->setParameter('formation', $formation)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Batch average rating + review counts for many formations (single query).
     *
     * @param list<int> $formationIds
     *
     * @return array<int, array{average: float, total: int}>
     */
    public function getAggregatesForFormationIds(array $formationIds): array
    {
        $formationIds = array_values(array_filter(array_map('intval', $formationIds)));
        if ([] === $formationIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->join('r.formation', 'f')
            ->select('f.id AS fid')
            ->addSelect('AVG(r.rating) AS avgRating')
            ->addSelect('COUNT(r.id) AS cnt')
            ->where('f.id IN (:ids)')
            ->setParameter('ids', $formationIds)
            ->groupBy('f.id')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $fid = (int) $row['fid'];
            $out[$fid] = [
                'average' => isset($row['avgRating']) ? round((float) $row['avgRating'], 2) : 0.0,
                'total' => (int) $row['cnt'],
            ];
        }

        return $out;
    }
}

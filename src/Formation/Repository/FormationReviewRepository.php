<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Entity\User;
use App\Formation\Entity\Formation;
use App\Formation\Entity\FormationReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FormationReview> */
class FormationReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationReview::class);
    }

    public function findOneForUserAndFormation(User $user, Formation $formation): ?FormationReview
    {
        return $this->findOneBy(['user' => $user, 'formation' => $formation]);
    }

    /** @return array{count: int, average: float} */
    public function summarizeForFormation(Formation $formation): array
    {
        /** @var array{reviewCount: numeric-string|int, averageRating: numeric-string|null} $row */
        $row = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS reviewCount, AVG(r.rating) AS averageRating')
            ->where('r.formation = :formation')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleResult();

        return [
            'count' => (int) $row['reviewCount'],
            'average' => $row['averageRating'] !== null ? round((float) $row['averageRating'], 1) : 0.0,
        ];
    }
}

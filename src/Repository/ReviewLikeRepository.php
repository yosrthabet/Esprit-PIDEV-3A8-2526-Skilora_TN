<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FormationReview;
use App\Entity\ReviewLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReviewLike>
 */
class ReviewLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewLike::class);
    }

    public function findOneByReviewAndUser(FormationReview $review, User $user): ?ReviewLike
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.review = :review')
            ->andWhere('rl.user = :user')
            ->setParameter('review', $review)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<int> $reviewIds
     *
     * @return array<int, int> reviewId => vote
     */
    public function getUserVotesForReviewIds(User $user, array $reviewIds): array
    {
        if ([] === $reviewIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('rl')
            ->select('IDENTITY(rl.review) AS reviewId, rl.vote AS vote')
            ->where('rl.user = :user')
            ->andWhere('rl.review IN (:reviewIds)')
            ->setParameter('user', $user)
            ->setParameter('reviewIds', $reviewIds)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['reviewId']] = (int) $row['vote'];
        }

        return $result;
    }
}

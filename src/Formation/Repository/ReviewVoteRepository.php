<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Entity\User;
use App\Formation\Entity\FormationReview;
use App\Formation\Entity\ReviewVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ReviewVote> */
class ReviewVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewVote::class);
    }

    public function findOneForUserAndReview(User $user, FormationReview $review): ?ReviewVote
    {
        return $this->findOneBy(['user' => $user, 'review' => $review]);
    }

    /** @return array{helpful: int, unhelpful: int} */
    public function countVotes(FormationReview $review): array
    {
        /** @var list<array{is_helpful: bool, total: int|string}> $rows */
        $rows = $this->createQueryBuilder('v')
            ->select('v.helpful AS is_helpful, COUNT(v.id) AS total')
            ->where('v.review = :review')
            ->setParameter('review', $review)
            ->groupBy('v.helpful')
            ->getQuery()
            ->getResult();

        $counts = ['helpful' => 0, 'unhelpful' => 0];
        foreach ($rows as $row) {
            if ($row['is_helpful']) {
                $counts['helpful'] = (int) $row['total'];
            } else {
                $counts['unhelpful'] = (int) $row['total'];
            }
        }

        return $counts;
    }
}

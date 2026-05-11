<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Finance\Entity\Contract;
use App\Finance\Entity\JobReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<JobReview> */
class JobReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobReview::class);
    }

    public function findOneForContractAndReviewer(Contract $contract, User $reviewer): ?JobReview
    {
        return $this->findOneBy(['contract' => $contract, 'reviewer' => $reviewer]);
    }

    /** @return list<JobReview> */
    public function findForUser(User $user): array
    {
        /** @var list<JobReview> $result */
        $result = $this->createQueryBuilder('r')
            ->where('r.reviewedUser = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function averageRatingForUser(User $user): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS avg_rating')
            ->where('r.reviewedUser = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? round((float) $result, 1) : null;
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reviewedUser = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

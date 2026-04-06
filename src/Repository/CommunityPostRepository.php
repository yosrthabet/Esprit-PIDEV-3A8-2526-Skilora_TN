<?php

namespace App\Repository;

use App\Entity\CommunityPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityPost>
 */
class CommunityPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityPost::class);
    }

    /**
     * @return CommunityPost[]
     */
    public function findRecentFeed(int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

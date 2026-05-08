<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\CommunityPostStatus;
use App\Community\Entity\CommunityPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityPost> */
class CommunityPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityPost::class);
    }

    /** @return list<CommunityPost> */
    public function findPublished(int $limit = 30): array
    {
        /** @var list<CommunityPost> $posts */
        $posts = $this->createQueryBuilder('p')
            ->join('p.author', 'a')->addSelect('a')
            ->leftJoin('p.comments', 'c')->addSelect('c')
            ->leftJoin('c.author', 'ca')->addSelect('ca')
            ->where('p.status = :status')
            ->setParameter('status', CommunityPostStatus::PUBLISHED)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $posts;
    }

    /** @return list<CommunityPost> */
    public function findForModeration(int $limit = 80): array
    {
        /** @var list<CommunityPost> $posts */
        $posts = $this->createQueryBuilder('p')
            ->join('p.author', 'a')->addSelect('a')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $posts;
    }
}

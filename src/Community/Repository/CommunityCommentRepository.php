<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityComment;
use App\Community\Entity\CommunityPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityComment> */
class CommunityCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityComment::class);
    }

    /** @return list<CommunityComment> */
    public function findTopLevelForPost(CommunityPost $post): array
    {
        /** @var list<CommunityComment> $comments */
        $comments = $this->createQueryBuilder('c')
            ->join('c.author', 'a')->addSelect('a')
            ->leftJoin('c.replies', 'r')->addSelect('r')
            ->leftJoin('r.author', 'ra')->addSelect('ra')
            ->where('c.post = :post')
            ->andWhere('c.parent IS NULL')
            ->setParameter('post', $post)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $comments;
    }
}

<?php

namespace App\Repository;

use App\Entity\CommunityPost;
use App\Entity\PostLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostLike::class);
    }

    public function findByPostAndUser(CommunityPost|int $post, User $user): ?PostLike
    {
        $postId = $post instanceof CommunityPost ? $post->getId() : $post;
        return $this->createQueryBuilder('l')
            ->where('l.post = :postId')
            ->andWhere('l.user = :userId')
            ->setParameter('postId', $postId)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByPost(int $postId): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.post = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

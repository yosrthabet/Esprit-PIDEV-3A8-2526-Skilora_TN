<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityLike;
use App\Community\Entity\CommunityPost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityLike> */
class CommunityLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityLike::class);
    }

    public function findOneForUserAndPost(User $user, CommunityPost $post): ?CommunityLike
    {
        return $this->findOneBy(['user' => $user, 'post' => $post]);
    }
}

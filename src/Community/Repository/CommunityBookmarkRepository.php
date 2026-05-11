<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityBookmark;
use App\Community\Entity\CommunityPost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityBookmark> */
class CommunityBookmarkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityBookmark::class);
    }

    public function findOneForUserAndPost(User $user, CommunityPost $post): ?CommunityBookmark
    {
        try {
            return $this->findOneBy(['user' => $user, 'post' => $post]);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<CommunityPost> */
    public function findPostsForUser(User $user, int $limit = 50): array
    {
        try {
            /** @var list<CommunityBookmark> $rows */
            $rows = $this->createQueryBuilder('b')
                ->join('b.post', 'p')->addSelect('p')
                ->join('p.author', 'a')->addSelect('a')
                ->where('b.user = :user')
                ->setParameter('user', $user)
                ->orderBy('b.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Throwable) {
            return [];
        }

        $posts = [];
        foreach ($rows as $bookmark) {
            $posts[] = $bookmark->getPost();
        }

        return $posts;
    }
}

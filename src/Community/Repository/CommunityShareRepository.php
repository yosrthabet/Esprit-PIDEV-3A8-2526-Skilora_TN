<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityPost;
use App\Community\Entity\CommunityShare;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityShare>
 */
class CommunityShareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityShare::class);
    }

    public function findOneForUserAndPost(User $user, CommunityPost $post): ?CommunityShare
    {
        return $this->findOneBy(['user' => $user, 'post' => $post]);
    }

    /** @return list<int> */
    public function findSharedPostIdsForUser(User $user, array $posts): array
    {
        if (empty($posts)) {
            return [];
        }
        $ids = array_map(fn($p) => $p->getId(), $posts);
        $rows = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.post) AS post_id')
            ->where('s.user = :user')
            ->andWhere('s.post IN (:posts)')
            ->setParameter('user', $user)
            ->setParameter('posts', $ids)
            ->getQuery()
            ->getScalarResult();

        return array_map(fn($r) => (int) $r['post_id'], $rows);
    }
}

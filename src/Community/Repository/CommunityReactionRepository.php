<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\CommunityReactionType;
use App\Community\Entity\CommunityPost;
use App\Community\Entity\CommunityReaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityReaction> */
class CommunityReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityReaction::class);
    }

    public function findOneForUserAndPost(User $user, CommunityPost $post): ?CommunityReaction
    {
        try {
            return $this->findOneBy(['user' => $user, 'post' => $post]);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<string, int> */
    public function countByTypeForPost(CommunityPost $post): array
    {
        try {
            $rows = $this->createQueryBuilder('r')
                ->select('r.type AS type, COUNT(r.id) AS total')
                ->where('r.post = :post')
                ->setParameter('post', $post)
                ->groupBy('r.type')
                ->getQuery()
                ->getArrayResult();
        } catch (\Throwable) {
            $rows = [];
        }

        $counts = [];
        foreach (CommunityReactionType::cases() as $type) {
            $counts[$type->value] = 0;
        }
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rawType = $row['type'] ?? null;
            $rawTotal = $row['total'] ?? null;
            $type = $rawType instanceof CommunityReactionType ? $rawType->value : (is_string($rawType) ? $rawType : null);
            if ($type !== null && is_numeric($rawTotal)) {
                $counts[$type] = (int) $rawTotal;
            }
        }

        return $counts;
    }

    /**
     * @param list<CommunityPost> $posts
     * @return array<int, array<string, int>>
     */
    public function countByTypeForPosts(array $posts): array
    {
        if ($posts === []) {
            return [];
        }

        try {
            $rows = $this->createQueryBuilder('r')
                ->select('IDENTITY(r.post) AS post_id, r.type AS type, COUNT(r.id) AS total')
                ->where('r.post IN (:posts)')
                ->setParameter('posts', $posts)
                ->groupBy('r.post')
                ->addGroupBy('r.type')
                ->getQuery()
                ->getArrayResult();
        } catch (\Throwable) {
            $rows = [];
        }

        $counts = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rawPostId = $row['post_id'] ?? null;
            $rawType = $row['type'] ?? null;
            $rawTotal = $row['total'] ?? null;
            $type = $rawType instanceof CommunityReactionType ? $rawType->value : (is_string($rawType) ? $rawType : null);
            if (is_numeric($rawPostId) && $type !== null && is_numeric($rawTotal)) {
                $counts[(int) $rawPostId][$type] = (int) $rawTotal;
            }
        }

        return $counts;
    }
}

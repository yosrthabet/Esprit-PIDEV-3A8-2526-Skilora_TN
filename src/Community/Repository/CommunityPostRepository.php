<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\CommunityPostStatus;
use App\Community\Entity\CommunityGroup;
use App\Community\Entity\CommunityPost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityPost> */
class CommunityPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityPost::class);
    }

    /**
     * @param list<User> $connections
     * @return list<CommunityPost>
     */
    public function findVisibleForUser(User $user, array $connections = [], int $limit = 30, int $offset = 0): array
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
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $posts;
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
    public function findForGroup(CommunityGroup $group, int $limit = 30): array
    {
        /** @var list<CommunityPost> $posts */
        $posts = $this->createQueryBuilder('p')
            ->join('p.author', 'a')->addSelect('a')
            ->leftJoin('p.comments', 'c')->addSelect('c')
            ->leftJoin('c.author', 'ca')->addSelect('ca')
            ->where('p.group = :group')
            ->andWhere('p.status = :status')
            ->setParameter('group', $group)
            ->setParameter('status', CommunityPostStatus::PUBLISHED)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $posts;
    }

    /**
     * @param list<User> $connections
     * @return list<CommunityPost>
     */
    public function findForConnections(User $user, array $connections, int $limit = 30): array
    {
        $authorIds = array_values(array_filter(array_map(static fn (User $connection): ?int => $connection->getId(), $connections)));
        if ($authorIds === []) {
            return [];
        }

        /** @var list<CommunityPost> $posts */
        $posts = $this->createQueryBuilder('p')
            ->join('p.author', 'a')->addSelect('a')
            ->leftJoin('p.comments', 'c')->addSelect('c')
            ->leftJoin('c.author', 'ca')->addSelect('ca')
            ->where('p.status = :status')
            ->andWhere('a.id IN (:authorIds)')
            ->setParameter('status', CommunityPostStatus::PUBLISHED)
            ->setParameter('authorIds', $authorIds)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $posts;
    }

    /** @return list<CommunityPost> */
    public function search(string $query, int $limit = 30): array
    {
        /** @var list<CommunityPost> $posts */
        $posts = $this->createQueryBuilder('p')
            ->join('p.author', 'a')->addSelect('a')
            ->where('p.status = :status')
            ->andWhere('p.content LIKE :q OR a.firstName LIKE :q OR a.lastName LIKE :q')
            ->setParameter('status', CommunityPostStatus::PUBLISHED)
            ->setParameter('q', '%' . $query . '%')
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

<?php

namespace App\Repository;

use App\Entity\CommunityGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommunityGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityGroup::class);
    }

    /** @return CommunityGroup[] */
    public function findPublicGroups(int $limit = 20): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.creator', 'c')
            ->addSelect('c')
            ->where('g.isPublic = true')
            ->orderBy('g.memberCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return CommunityGroup[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.creator', 'c')
            ->addSelect('c')
            ->orderBy('g.memberCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

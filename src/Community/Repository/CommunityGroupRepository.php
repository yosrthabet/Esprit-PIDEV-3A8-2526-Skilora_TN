<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityGroup> */
class CommunityGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityGroup::class);
    }

    /** @return list<CommunityGroup> */
    public function findRecent(int $limit = 40): array
    {
        /** @var list<CommunityGroup> $groups */
        $groups = $this->createQueryBuilder('g')
            ->join('g.owner', 'o')->addSelect('o')
            ->orderBy('g.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $groups;
    }
}

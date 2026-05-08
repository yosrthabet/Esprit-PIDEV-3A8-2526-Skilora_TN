<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityEvent> */
class CommunityEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityEvent::class);
    }

    /** @return list<CommunityEvent> */
    public function findUpcoming(int $limit = 40): array
    {
        /** @var list<CommunityEvent> $events */
        $events = $this->createQueryBuilder('e')
            ->join('e.host', 'h')->addSelect('h')
            ->orderBy('e.startsAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $events;
    }
}

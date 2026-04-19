<?php

namespace App\Repository;

use App\Entity\CommunityEvent;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommunityEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityEvent::class);
    }

    /** @return CommunityEvent[] */
    public function findUpcoming(int $limit = 20): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.organizer', 'o')
            ->addSelect('o')
            ->where('e.status = :status')
            ->setParameter('status', CommunityEvent::STATUS_UPCOMING)
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return CommunityEvent[] */
    public function findAll(): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.organizer', 'o')
            ->addSelect('o')
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return CommunityEvent[] */
    public function findByOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUpcoming(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->setParameter('status', CommunityEvent::STATUS_UPCOMING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

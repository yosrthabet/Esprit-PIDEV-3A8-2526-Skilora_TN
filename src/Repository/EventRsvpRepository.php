<?php

namespace App\Repository;

use App\Entity\EventRsvp;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRsvpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRsvp::class);
    }

    public function findByEventAndUser(int $eventId, User $user): ?EventRsvp
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :eventId')
            ->andWhere('r.user = :userId')
            ->setParameter('eventId', $eventId)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByEvent(int $eventId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.event = :eventId')
            ->andWhere('r.status = :status')
            ->setParameter('eventId', $eventId)
            ->setParameter('status', EventRsvp::STATUS_GOING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

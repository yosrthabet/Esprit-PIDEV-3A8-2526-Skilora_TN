<?php

namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function findOneByTicket(Ticket $ticket): ?Feedback
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAverageRating(): float
    {
        $avg = $this->createQueryBuilder('f')
            ->select('AVG(f.rating)')
            ->getQuery()
            ->getSingleScalarResult();

        return $avg !== null ? (float) $avg : 0.0;
    }
}

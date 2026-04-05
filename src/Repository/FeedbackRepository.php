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

    /** @return array<int, int> star => count */
    public function getRatingDistribution(): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('f.rating as rating, COUNT(f.id) as total')
            ->groupBy('f.rating')
            ->getQuery()
            ->getArrayResult();

        $result = array_fill(1, 5, 0);
        foreach ($rows as $row) {
            $result[(int) $row['rating']] = (int) $row['total'];
        }

        return $result;
    }

    /** @return Feedback[] */
    public function search(int $page = 1, int $limit = 5): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

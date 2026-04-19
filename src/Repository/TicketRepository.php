<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * @return Ticket[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.userId = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('t.createdDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[]
     */
    public function searchByUser(int $userId, string $query = ''): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.userId = :uid')
            ->setParameter('uid', $userId);

        if ($query) {
            $qb->andWhere('t.subject LIKE :q OR t.category LIKE :q OR t.description LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        return $qb->orderBy('t.createdDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as total')
            ->groupBy('t.status')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['status']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    public function countByPriority(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.priority as p, COUNT(t.id) as total')
            ->groupBy('t.priority')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['p']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * @return Ticket[]
     */
    public function search(string $query = '', int $page = 1, int $limit = 5, ?string $status = null, string $sortBy = 'latest'): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($query) {
            $qb->andWhere('t.subject LIKE :q OR t.category LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if ($sortBy === 'oldest') {
            $qb->orderBy('t.createdDate', 'ASC');
        } elseif ($sortBy === 'priority') {
            // Need a custom order for priority if it's enum, or just sort alphabetically. 
            // In SQL, we can't easily custom sort without CASE unless it's integers.
            // Let's sort by priority field.
            $qb->orderBy('t.priority', 'ASC');
        } else {
            $qb->orderBy('t.createdDate', 'DESC');
        }

        return $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countTotal(string $query = '', ?string $status = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        if ($query) {
            $qb->andWhere('t.subject LIKE :q OR t.category LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countByCategory(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.category as cat, COUNT(t.id) as total')
            ->groupBy('t.category')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['cat']] = (int) $row['total'];
        }

        return $result;
    }

    public function countLast7DaysVolume(): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days")->format('Y-m-d');
            $days[$date] = 0;
        }

        $rows = $this->createQueryBuilder('t')
            ->select('SUBSTRING(t.createdDate, 1, 10) as day, COUNT(t.id) as total')
            ->where('t.createdDate >= :start')
            ->setParameter('start', (new \DateTime())->modify('-7 days')->format('Y-m-d 00:00:00'))
            ->groupBy('day')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            if (isset($days[$row['day']])) {
                $days[$row['day']] = (int) $row['total'];
            }
        }

        return $days;
    }
}

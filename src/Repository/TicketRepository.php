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
    public function findByUser(int $utilisateurId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.utilisateurId = :uid')
            ->setParameter('uid', $utilisateurId)
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[]
     */
    public function searchByUser(int $utilisateurId, string $query = ''): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.utilisateurId = :uid')
            ->setParameter('uid', $utilisateurId);

        if ($query) {
            $qb->andWhere('t.subject LIKE :q OR t.categorie LIKE :q OR t.description LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        return $qb->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.statut as status, COUNT(t.id) as total')
            ->groupBy('t.statut')
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
            ->select('t.priorite as p, COUNT(t.id) as total')
            ->groupBy('t.priorite')
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
    public function search(string $query = '', int $page = 1, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($query) {
            $qb->where('t.subject LIKE :q OR t.categorie LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        return $qb->orderBy('t.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByCategory(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.categorie as cat, COUNT(t.id) as total')
            ->groupBy('t.categorie')
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
            ->select('SUBSTRING(t.dateCreation, 1, 10) as day, COUNT(t.id) as total')
            ->where('t.dateCreation >= :start')
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

    public function countTotal(string $query = ''): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        if ($query) {
            $qb->where('t.subject LIKE :q OR t.categorie LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

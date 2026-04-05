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

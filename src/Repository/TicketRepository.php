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
}

<?php

namespace App\Repository;

use App\Entity\MessageTicket;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageTicket>
 */
class MessageTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageTicket::class);
    }

    /**
     * @return MessageTicket[]
     */
    public function findByTicket(Ticket $ticket, bool $includeInternal = true): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('m.dateEnvoi', 'ASC');

        if (!$includeInternal) {
            $qb->andWhere('m.isInternal = false');
        }

        return $qb->getQuery()->getResult();
    }
}

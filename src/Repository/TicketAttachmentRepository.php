<?php

namespace App\Repository;

use App\Entity\TicketAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketAttachment>
 */
class TicketAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketAttachment::class);
    }

    /**
     * @return TicketAttachment[]
     */
    public function findByTicket(int $ticketId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.ticket = :ticketId')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('a.createdDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TicketAttachment[]
     */
    public function findByMessage(int $messageId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.message = :messageId')
            ->setParameter('messageId', $messageId)
            ->orderBy('a.createdDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

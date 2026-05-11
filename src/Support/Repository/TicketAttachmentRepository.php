<?php

declare(strict_types=1);

namespace App\Support\Repository;

use App\Support\Entity\SupportTicket;
use App\Support\Entity\TicketAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TicketAttachment> */
class TicketAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketAttachment::class);
    }

    /** @return list<TicketAttachment> */
    public function findForTicket(SupportTicket $ticket): array
    {
        /** @var list<TicketAttachment> $result */
        $result = $this->findBy(['ticket' => $ticket], ['uploadedAt' => 'ASC']);

        return $result;
    }
}

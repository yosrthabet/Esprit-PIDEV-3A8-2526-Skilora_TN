<?php

declare(strict_types=1);

namespace App\Support\Repository;

use App\Entity\User;
use App\Enum\TicketStatus;
use App\Support\Entity\SupportTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SupportTicket> */
class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    /** @return list<SupportTicket> */
    public function findForUser(User $user, ?string $query = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.requester = :user')
            ->setParameter('user', $user)
            ->orderBy('t.updatedAt', 'DESC');

        if ($query !== null && trim($query) !== '') {
            $qb->andWhere('LOWER(t.subject) LIKE :q OR LOWER(t.description) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower(trim($query)) . '%');
        }

        /** @var list<SupportTicket> $tickets */
        $tickets = $qb->getQuery()->getResult();

        return $tickets;
    }

    /** @return list<SupportTicket> */
    public function findForAdmin(?string $status = null, ?string $query = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.requester', 'u')->addSelect('u')
            ->orderBy('t.updatedAt', 'DESC');

        $ticketStatus = is_string($status) ? TicketStatus::tryFrom($status) : null;
        if ($ticketStatus !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $ticketStatus);
        }

        if ($query !== null && trim($query) !== '') {
            $qb->andWhere('LOWER(t.subject) LIKE :q OR LOWER(t.description) LIKE :q OR LOWER(u.username) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower(trim($query)) . '%');
        }

        /** @var list<SupportTicket> $tickets */
        $tickets = $qb->getQuery()->getResult();

        return $tickets;
    }
}

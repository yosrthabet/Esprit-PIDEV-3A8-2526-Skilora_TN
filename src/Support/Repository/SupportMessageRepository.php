<?php

declare(strict_types=1);

namespace App\Support\Repository;

use App\Support\Entity\SupportMessage;
use App\Support\Entity\SupportTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SupportMessage> */
class SupportMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportMessage::class);
    }

    /** @return list<SupportMessage> */
    public function findVisibleForTicket(SupportTicket $ticket, bool $admin = false, int $afterId = 0): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.sender', 's')->addSelect('s')
            ->where('m.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('m.createdAt', 'ASC');

        if (!$admin) {
            $qb->andWhere('m.internalNote = false');
        }
        if ($afterId > 0) {
            $qb->andWhere('m.id > :afterId')->setParameter('afterId', $afterId);
        }

        /** @var list<SupportMessage> $messages */
        $messages = $qb->getQuery()->getResult();

        return $messages;
    }
}

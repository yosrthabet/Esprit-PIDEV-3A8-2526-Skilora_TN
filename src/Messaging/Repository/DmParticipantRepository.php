<?php

declare(strict_types=1);

namespace App\Messaging\Repository;

use App\Entity\User;
use App\Messaging\Entity\DmConversation;
use App\Messaging\Entity\DmParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DmParticipant> */
class DmParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DmParticipant::class);
    }

    public function findOneForUserAndConversation(User $user, DmConversation $conversation): ?DmParticipant
    {
        return $this->findOneBy(['user' => $user, 'conversation' => $conversation]);
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('SUM(p.unreadCount)')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

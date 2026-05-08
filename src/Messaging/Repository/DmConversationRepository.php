<?php

declare(strict_types=1);

namespace App\Messaging\Repository;

use App\Entity\User;
use App\Messaging\Entity\DmConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DmConversation> */
class DmConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DmConversation::class);
    }

    /** @return list<DmConversation> */
    public function findForUser(User $user): array
    {
        /** @var list<DmConversation> $conversations */
        $conversations = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->join('c.participants', 'allp')->addSelect('allp')
            ->join('allp.user', 'participantUser')->addSelect('participantUser')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->addOrderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $conversations;
    }

    public function findOneDirectConversation(User $one, User $two): ?DmConversation
    {
        /** @var list<DmConversation> $conversations */
        $conversations = $this->createQueryBuilder('c')
            ->join('c.participants', 'p1')
            ->join('c.participants', 'p2')
            ->where('p1.user = :one')
            ->andWhere('p2.user = :two')
            ->setParameter('one', $one)
            ->setParameter('two', $two)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $conversations[0] ?? null;
    }
}

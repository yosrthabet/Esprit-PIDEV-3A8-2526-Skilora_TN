<?php

namespace App\Repository;

use App\Entity\DmConversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DmConversation>
 */
class DmConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DmConversation::class);
    }

    public function findBetweenUsers(User $a, User $b): ?DmConversation
    {
        $low = $a->getId() <= $b->getId() ? $a : $b;
        $high = $a->getId() <= $b->getId() ? $b : $a;

        return $this->findOneBy([
            'participantLow' => $low,
            'participantHigh' => $high,
        ]);
    }

    /**
     * @return DmConversation[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->addSelect('m')
            ->where('c.participantLow = :u OR c.participantHigh = :u')
            ->setParameter('u', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

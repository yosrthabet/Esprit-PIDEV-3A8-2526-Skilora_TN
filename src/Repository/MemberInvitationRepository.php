<?php

namespace App\Repository;

use App\Entity\MemberInvitation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MemberInvitation>
 */
class MemberInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberInvitation::class);
    }

    /**
     * @return MemberInvitation[]
     */
    public function findReceivedBy(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.invitee = :u')
            ->setParameter('u', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MemberInvitation[]
     */
    public function findSentBy(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.inviter = :u')
            ->setParameter('u', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingBetween(User $inviter, User $invitee): ?MemberInvitation
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.inviter = :a AND i.invitee = :b')
            ->andWhere('i.status = :pending')
            ->setParameter('a', $inviter)
            ->setParameter('b', $invitee)
            ->setParameter('pending', MemberInvitation::STATUS_PENDING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function areFriends(User $a, User $b): bool
    {
        if ($a->getId() === null || $b->getId() === null || $a->getId() === $b->getId()) {
            return false;
        }

        return null !== $this->createQueryBuilder('i')
            ->where('i.status = :accepted')
            ->andWhere('(i.inviter = :a AND i.invitee = :b) OR (i.inviter = :b AND i.invitee = :a)')
            ->setParameter('accepted', MemberInvitation::STATUS_ACCEPTED)
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Membres avec qui une invitation a été acceptée (dans un sens ou l’autre).
     *
     * @return User[]
     */
    public function findFriendsFor(User $user): array
    {
        $idsInviter = $this->createQueryBuilder('i')
            ->select('IDENTITY(i.invitee)')
            ->where('i.inviter = :u')
            ->andWhere('i.status = :acc')
            ->setParameter('u', $user)
            ->setParameter('acc', MemberInvitation::STATUS_ACCEPTED)
            ->getQuery()
            ->getSingleColumnResult();

        $idsInvitee = $this->createQueryBuilder('i')
            ->select('IDENTITY(i.inviter)')
            ->where('i.invitee = :u')
            ->andWhere('i.status = :acc')
            ->setParameter('u', $user)
            ->setParameter('acc', MemberInvitation::STATUS_ACCEPTED)
            ->getQuery()
            ->getSingleColumnResult();

        $ids = array_values(array_unique(array_merge($idsInviter, $idsInvitee)));
        if ($ids === []) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPendingFor(User $user): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.invitee = :u')
            ->andWhere('i.status = :pending')
            ->setParameter('u', $user)
            ->setParameter('pending', MemberInvitation::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

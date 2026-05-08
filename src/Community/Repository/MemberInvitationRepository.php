<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\MemberInvitation;
use App\Community\MemberInvitationStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<MemberInvitation> */
class MemberInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberInvitation::class);
    }

    /** @return list<MemberInvitation> */
    public function findReceivedBy(User $user): array
    {
        /** @var list<MemberInvitation> $invitations */
        $invitations = $this->createQueryBuilder('i')
            ->join('i.inviter', 'inviter')->addSelect('inviter')
            ->where('i.invitee = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $invitations;
    }

    /** @return list<MemberInvitation> */
    public function findSentBy(User $user): array
    {
        /** @var list<MemberInvitation> $invitations */
        $invitations = $this->createQueryBuilder('i')
            ->join('i.invitee', 'invitee')->addSelect('invitee')
            ->where('i.inviter = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $invitations;
    }

    public function findPendingBetween(User $a, User $b): ?MemberInvitation
    {
        if (!$this->canCompare($a, $b)) {
            return null;
        }

        $invitation = $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('(i.inviter = :a AND i.invitee = :b) OR (i.inviter = :b AND i.invitee = :a)')
            ->setParameter('status', MemberInvitationStatus::PENDING)
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $invitation instanceof MemberInvitation ? $invitation : null;
    }

    public function areFriends(User $a, User $b): bool
    {
        if (!$this->canCompare($a, $b)) {
            return false;
        }

        return null !== $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->andWhere('(i.inviter = :a AND i.invitee = :b) OR (i.inviter = :b AND i.invitee = :a)')
            ->setParameter('status', MemberInvitationStatus::ACCEPTED)
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<User> */
    public function findFriendsFor(User $user): array
    {
        if ($user->getId() === null) {
            return [];
        }

        /** @var list<MemberInvitation> $accepted */
        $accepted = $this->createQueryBuilder('i')
            ->join('i.inviter', 'inviter')->addSelect('inviter')
            ->join('i.invitee', 'invitee')->addSelect('invitee')
            ->where('i.status = :status')
            ->andWhere('i.inviter = :user OR i.invitee = :user')
            ->setParameter('status', MemberInvitationStatus::ACCEPTED)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        /** @var array<int, User> $friends */
        $friends = [];
        foreach ($accepted as $invitation) {
            $friend = $invitation->getInviter()->getId() === $user->getId() ? $invitation->getInvitee() : $invitation->getInviter();
            $friendId = $friend->getId();
            if ($friendId !== null && $friend->isActive() && !$friend->isAdmin()) {
                $friends[$friendId] = $friend;
            }
        }

        usort($friends, static fn (User $a, User $b): int => strcasecmp($a->getDisplayName(), $b->getDisplayName()));

        return array_values($friends);
    }

    /** @return list<User> */
    public function findAvailableInvitees(User $user): array
    {
        if ($user->getId() === null) {
            return [];
        }

        /** @var list<User> $users */
        $users = $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.active = true')
            ->andWhere('u.id != :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('u.fullName', 'ASC')
            ->addOrderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter($users, fn (User $candidate): bool => !$candidate->isAdmin()
            && !$this->areFriends($user, $candidate)
            && $this->findPendingBetween($user, $candidate) === null));
    }

    private function canCompare(User $a, User $b): bool
    {
        return $a->getId() !== null && $b->getId() !== null && $a->getId() !== $b->getId();
    }
}

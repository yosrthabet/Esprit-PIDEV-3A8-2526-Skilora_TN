<?php

namespace App\Repository;

use App\Entity\GroupMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GroupMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMember::class);
    }

    public function findMembership(int $groupId, User $user): ?GroupMember
    {
        return $this->createQueryBuilder('m')
            ->where('m.group = :groupId')
            ->andWhere('m.user = :userId')
            ->setParameter('groupId', $groupId)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isMember(int $groupId, User $user): bool
    {
        return $this->findMembership($groupId, $user) !== null;
    }

    /** @return GroupMember[] */
    public function findByGroup(int $groupId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->addSelect('u')
            ->where('m.group = :groupId')
            ->setParameter('groupId', $groupId)
            ->orderBy('m.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

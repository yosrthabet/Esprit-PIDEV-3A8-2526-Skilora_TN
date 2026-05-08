<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityGroup;
use App\Community\Entity\GroupMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<GroupMember> */
class GroupMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMember::class);
    }

    public function findOneForUserAndGroup(User $user, CommunityGroup $group): ?GroupMember
    {
        return $this->findOneBy(['user' => $user, 'group' => $group]);
    }
}

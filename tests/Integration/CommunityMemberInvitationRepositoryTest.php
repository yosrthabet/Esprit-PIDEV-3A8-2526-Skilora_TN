<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Community\Entity\MemberInvitation;
use App\Community\MemberInvitationStatus;
use App\Community\Repository\MemberInvitationRepository;
use App\Entity\User;

final class CommunityMemberInvitationRepositoryTest extends DatabaseTestCase
{
    public function testAcceptedInvitationsDefineAvailableMessagingFriends(): void
    {
        $me = $this->createTestUser(['username' => 'network_me_' . bin2hex(random_bytes(3))]);
        $friend = $this->createTestUser(['username' => 'network_friend_' . bin2hex(random_bytes(3))]);
        $pending = $this->createTestUser(['username' => 'network_pending_' . bin2hex(random_bytes(3))]);
        $admin = $this->createTestUser(['username' => 'network_admin_' . bin2hex(random_bytes(3)), 'role' => 'ADMIN']);

        $acceptedInvitation = (new MemberInvitation())
            ->setInviter($me)
            ->setInvitee($friend)
            ->setStatus(MemberInvitationStatus::ACCEPTED)
            ->recordResponse();
        $pendingInvitation = (new MemberInvitation())
            ->setInviter($pending)
            ->setInvitee($me);
        $adminInvitation = (new MemberInvitation())
            ->setInviter($me)
            ->setInvitee($admin)
            ->setStatus(MemberInvitationStatus::ACCEPTED)
            ->recordResponse();

        $this->em->persist($acceptedInvitation);
        $this->em->persist($pendingInvitation);
        $this->em->persist($adminInvitation);
        $this->em->flush();

        $repository = $this->repository();
        $friends = $repository->findFriendsFor($me);

        self::assertTrue($repository->areFriends($me, $friend));
        self::assertFalse($repository->areFriends($me, $pending));
        self::assertNotNull($repository->findPendingBetween($me, $pending));
        self::assertSame([$friend->getId()], array_map(static fn (User $user): ?int => $user->getId(), $friends));
    }

    private function repository(): MemberInvitationRepository
    {
        $repository = $this->em->getRepository(MemberInvitation::class);
        self::assertInstanceOf(MemberInvitationRepository::class, $repository);

        return $repository;
    }
}

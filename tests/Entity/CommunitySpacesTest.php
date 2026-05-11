<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Community\BlogArticleStatus;
use App\Community\Entity\BlogArticle;
use App\Community\Entity\CommunityEvent;
use App\Community\Entity\CommunityGroup;
use App\Community\Entity\EventRsvp;
use App\Community\Entity\MemberInvitation;
use App\Community\MemberInvitationStatus;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class CommunitySpacesTest extends TestCase
{
    public function testGroupMemberCreationUpdatesCount(): void
    {
        $owner = (new User())->setUsername('owner')->setRole('USER');
        $group = (new CommunityGroup())->setOwner($owner)->setName('Symfony Builders');
        $group->setPrivacy('private')->setImagePath('uploads/community/group.png');
        $member = $group->addMember($owner, 'owner');

        self::assertSame('Symfony Builders', $group->getName());
        self::assertSame('private', $group->getPrivacy());
        self::assertSame('uploads/community/group.png', $group->getImagePath());
        self::assertSame(1, $group->getMembersCount());
        self::assertSame($group, $member->getGroup());
        self::assertSame('owner', $member->getRole());
    }

    public function testEventRsvpAndBlogArticle(): void
    {
        $user = (new User())->setUsername('writer')->setRole('TRAINER');
        $event = (new CommunityEvent())->setHost($user)->setTitle('Portfolio clinic');
        $rsvp = (new EventRsvp())->setEvent($event)->setUser($user);
        $event->incrementRsvps();
        $article = (new BlogArticle())
            ->setAuthor($user)
            ->setTitle('How to scope freelance work')
            ->setSlug('scope-freelance-work')
            ->setContent('Practical notes')
            ->setStatus(BlogArticleStatus::PUBLISHED);

        self::assertSame(1, $event->getRsvpsCount());
        self::assertSame($event, $rsvp->getEvent());
        self::assertTrue($article->isPublished());
        self::assertSame('scope-freelance-work', $article->getSlug());
    }

    public function testMemberInvitationLifecycle(): void
    {
        $inviter = (new User())->setUsername('sender')->setRole('USER');
        $invitee = (new User())->setUsername('receiver')->setRole('TRAINER');
        $invitation = (new MemberInvitation())
            ->setInviter($inviter)
            ->setInvitee($invitee)
            ->setNote('  Let us connect  ');

        self::assertSame($inviter, $invitation->getInviter());
        self::assertSame($invitee, $invitation->getInvitee());
        self::assertSame(MemberInvitationStatus::PENDING, $invitation->getStatus());
        self::assertTrue($invitation->isPending());
        self::assertSame('Let us connect', $invitation->getNote());

        $invitation->setStatus(MemberInvitationStatus::ACCEPTED)->recordResponse();

        self::assertFalse($invitation->isPending());
        self::assertSame(MemberInvitationStatus::ACCEPTED, $invitation->getStatus());
        self::assertNotNull($invitation->getRespondedAt());
    }
}

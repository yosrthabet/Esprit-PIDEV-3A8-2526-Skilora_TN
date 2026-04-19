<?php

namespace App\Tests\Integration;

use App\Entity\BlogArticle;
use App\Entity\CommunityEvent;
use App\Entity\CommunityGroup;
use App\Entity\CommunityNotification;
use App\Entity\CommunityPost;
use App\Entity\DmConversation;
use App\Entity\DmMessage;
use App\Entity\EventRsvp;
use App\Entity\GroupMember;
use App\Entity\MemberInvitation;
use App\Entity\PostComment;
use App\Entity\PostLike;

class CommunityCrudTest extends DatabaseTestCase
{
    public function testCreateCommunityPost(): void
    {
        $user = $this->createTestUser();
        $post = new CommunityPost();
        $post->setAuthor($user);
        $post->setContent('Hello World from integration test!');
        $this->em->persist($post);
        $this->em->flush();

        $this->assertNotNull($post->getId());
    }

    public function testReadCommunityPost(): void
    {
        $user = $this->createTestUser();
        $post = new CommunityPost();
        $post->setAuthor($user);
        $post->setContent('Read me');
        $this->em->persist($post);
        $this->em->flush();
        $id = $post->getId();
        $this->em->clear();

        $found = $this->em->find(CommunityPost::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Read me', $found->getContent());
    }

    public function testUpdateCommunityPost(): void
    {
        $user = $this->createTestUser();
        $post = new CommunityPost();
        $post->setAuthor($user);
        $post->setContent('Original');
        $this->em->persist($post);
        $this->em->flush();

        $post->setContent('Updated content');
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(CommunityPost::class, $post->getId());
        $this->assertSame('Updated content', $found->getContent());
    }

    public function testDeleteCommunityPost(): void
    {
        $user = $this->createTestUser();
        $post = new CommunityPost();
        $post->setAuthor($user);
        $post->setContent('Delete me');
        $this->em->persist($post);
        $this->em->flush();
        $id = $post->getId();

        $this->em->remove($post);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(CommunityPost::class, $id));
    }

    public function testDmConversation(): void
    {
        $a = $this->createTestUser(['username' => 'dm_alice']);
        $b = $this->createTestUser(['username' => 'dm_bob']);

        $conv = DmConversation::forUsers($a, $b);
        $this->em->persist($conv);
        $this->em->flush();

        $this->assertNotNull($conv->getId());
    }

    public function testDmMessage(): void
    {
        $a = $this->createTestUser(['username' => 'msg_alice']);
        $b = $this->createTestUser(['username' => 'msg_bob']);

        $conv = DmConversation::forUsers($a, $b);
        $this->em->persist($conv);
        $this->em->flush();

        $msg = new DmMessage();
        $msg->setConversation($conv);
        $msg->setSender($a);
        $msg->setBody('Hey there!');
        $this->em->persist($msg);
        $this->em->flush();

        $this->assertNotNull($msg->getId());
    }

    public function testMemberInvitation(): void
    {
        $inviter = $this->createTestUser(['username' => 'inviter']);
        $invitee = $this->createTestUser(['username' => 'invitee']);

        $inv = new MemberInvitation();
        $inv->setInviter($inviter);
        $inv->setInvitee($invitee);
        $inv->setNote('Join us!');
        $this->em->persist($inv);
        $this->em->flush();

        $this->assertNotNull($inv->getId());
        $this->assertSame('pending', $inv->getStatus());
    }

    // ── Blog Articles ──

    public function testCreateBlogArticle(): void
    {
        $user = $this->createTestUser();
        $article = new BlogArticle();
        $article->setAuthor($user);
        $article->setTitle('Test Article');
        $article->setContent('This is a test article with enough content.');
        $this->em->persist($article);
        $this->em->flush();

        $this->assertNotNull($article->getId());
    }

    public function testReadBlogArticle(): void
    {
        $user = $this->createTestUser();
        $a = new BlogArticle();
        $a->setAuthor($user);
        $a->setTitle('Read Article');
        $a->setContent('Content for reading test article.');
        $this->em->persist($a);
        $this->em->flush();
        $id = $a->getId();
        $this->em->clear();

        $found = $this->em->find(BlogArticle::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Read Article', $found->getTitle());
    }

    // ── Community Events ──

    public function testCreateCommunityEvent(): void
    {
        $user = $this->createTestUser();
        $event = new CommunityEvent();
        $event->setOrganizer($user);
        $event->setTitle('Symfony Meetup');
        $event->setStartDate(new \DateTimeImmutable('+7 days'));
        $this->em->persist($event);
        $this->em->flush();

        $this->assertNotNull($event->getId());
    }

    public function testReadCommunityEvent(): void
    {
        $user = $this->createTestUser();
        $event = new CommunityEvent();
        $event->setOrganizer($user);
        $event->setTitle('Read Event');
        $event->setStartDate(new \DateTimeImmutable('+7 days'));
        $this->em->persist($event);
        $this->em->flush();
        $id = $event->getId();
        $this->em->clear();

        $found = $this->em->find(CommunityEvent::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Read Event', $found->getTitle());
    }

    // ── Community Groups ──

    public function testCreateCommunityGroup(): void
    {
        $user = $this->createTestUser();
        $group = new CommunityGroup();
        $group->setName('PHP Devs');
        $group->setCreator($user);
        $this->em->persist($group);
        $this->em->flush();

        $this->assertNotNull($group->getId());
    }

    public function testReadCommunityGroup(): void
    {
        $user = $this->createTestUser();
        $group = new CommunityGroup();
        $group->setName('Read Group');
        $group->setCreator($user);
        $this->em->persist($group);
        $this->em->flush();
        $id = $group->getId();
        $this->em->clear();

        $found = $this->em->find(CommunityGroup::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Read Group', $found->getName());
    }

    // ── Notifications ──

    public function testCreateCommunityNotification(): void
    {
        $user = $this->createTestUser();
        $n = new CommunityNotification();
        $n->setUser($user);
        $n->setType('LIKE');
        $n->setTitle('New Like');
        $n->setMessage('Someone liked your post');
        $this->em->persist($n);
        $this->em->flush();

        $this->assertNotNull($n->getId());
        $this->assertFalse($n->isRead());
    }

    // ── Post Comments ──

    public function testCreatePostComment(): void
    {
        $user = $this->createTestUser();
        $post = new CommunityPost();
        $post->setAuthor($user);
        $post->setContent('Post for comment');
        $this->em->persist($post);
        $this->em->flush();

        $comment = new PostComment();
        $comment->setPost($post);
        $comment->setAuthor($user);
        $comment->setContent('Nice post!');
        $this->em->persist($comment);
        $this->em->flush();

        $this->assertNotNull($comment->getId());
    }

    // ── Post Likes ──

    public function testCreatePostLike(): void
    {
        $user = $this->createTestUser();
        $post = new CommunityPost();
        $post->setAuthor($user);
        $post->setContent('Post for like');
        $this->em->persist($post);
        $this->em->flush();

        $like = new PostLike();
        $like->setPost($post);
        $like->setUser($user);
        $this->em->persist($like);
        $this->em->flush();

        $this->assertNotNull($like->getId());
    }

    // ── Group Members ──

    public function testCreateGroupMember(): void
    {
        $creator = $this->createTestUser(['username' => 'grp_creator']);
        $member = $this->createTestUser(['username' => 'grp_member']);

        $group = new CommunityGroup();
        $group->setName('Test Group');
        $group->setCreator($creator);
        $this->em->persist($group);
        $this->em->flush();

        $gm = new GroupMember();
        $gm->setGroup($group);
        $gm->setUser($member);
        $gm->setRole(GroupMember::ROLE_MEMBER);
        $this->em->persist($gm);
        $this->em->flush();

        $this->assertNotNull($gm->getId());
    }

    // ── Event RSVPs ──

    public function testCreateEventRsvp(): void
    {
        $organizer = $this->createTestUser(['username' => 'evt_org']);
        $attendee = $this->createTestUser(['username' => 'evt_att']);

        $event = new CommunityEvent();
        $event->setOrganizer($organizer);
        $event->setTitle('RSVP Event');
        $event->setStartDate(new \DateTimeImmutable('+7 days'));
        $this->em->persist($event);
        $this->em->flush();

        $rsvp = new EventRsvp();
        $rsvp->setEvent($event);
        $rsvp->setUser($attendee);
        $rsvp->setStatus(EventRsvp::STATUS_GOING);
        $this->em->persist($rsvp);
        $this->em->flush();

        $this->assertNotNull($rsvp->getId());
    }

    // ── DM Messages new fields ──

    public function testDmMessageNewFields(): void
    {
        $a = $this->createTestUser(['username' => 'dm_new_a']);
        $b = $this->createTestUser(['username' => 'dm_new_b']);

        $conv = DmConversation::forUsers($a, $b);
        $this->em->persist($conv);
        $this->em->flush();

        $msg = new DmMessage();
        $msg->setConversation($conv);
        $msg->setSender($a);
        $msg->setBody('Voice message');
        $msg->setMessageType('voice');
        $msg->setVoiceUrl('https://cdn.test/voice.ogg');
        $msg->setIsRead(true);
        $msg->setReadAt(new \DateTimeImmutable());
        $this->em->persist($msg);
        $this->em->flush();

        $this->assertNotNull($msg->getId());
        $this->assertSame('voice', $msg->getMessageType());
        $this->assertTrue($msg->isRead());
    }

    // ── Community Post new fields ──

    public function testCommunityPostNewFields(): void
    {
        $user = $this->createTestUser();
        $post = new CommunityPost();
        $post->setAuthor($user);
        $post->setContent('Post with new fields');
        $post->setPostType('ARTICLE');
        $post->setLikesCount(5);
        $post->setCommentsCount(2);
        $post->setSharesCount(1);
        $this->em->persist($post);
        $this->em->flush();

        $this->em->clear();
        $found = $this->em->find(CommunityPost::class, $post->getId());
        $this->assertSame('ARTICLE', $found->getPostType());
        $this->assertSame(5, $found->getLikesCount());
        $this->assertSame(2, $found->getCommentsCount());
        $this->assertSame(1, $found->getSharesCount());
    }
}

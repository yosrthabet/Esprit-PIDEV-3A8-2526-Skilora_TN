<?php

namespace App\Tests\Entity;

use App\Entity\CommunityEvent;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CommunityEventTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('organizer');
        $u->setEmail('org@test.com');
        $u->setFullName('Organizer');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new CommunityEvent())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $e = new CommunityEvent();
        $org = $this->makeUser();
        $start = new \DateTimeImmutable('2026-06-01 10:00');
        $end = new \DateTimeImmutable('2026-06-01 12:00');

        $e->setOrganizer($org);
        $e->setTitle('Symfony Meetup');
        $e->setDescription('A great meetup');
        $e->setEventType(CommunityEvent::TYPE_MEETUP);
        $e->setLocation('Paris');
        $e->setIsOnline(false);
        $e->setStartDate($start);
        $e->setEndDate($end);
        $e->setMaxAttendees(50);
        $e->setImageUrl('https://img.test/event.jpg');

        $this->assertSame($org, $e->getOrganizer());
        $this->assertSame('Symfony Meetup', $e->getTitle());
        $this->assertSame('A great meetup', $e->getDescription());
        $this->assertSame(CommunityEvent::TYPE_MEETUP, $e->getEventType());
        $this->assertSame('Paris', $e->getLocation());
        $this->assertFalse($e->isOnline());
        $this->assertSame($start, $e->getStartDate());
        $this->assertSame($end, $e->getEndDate());
        $this->assertSame(50, $e->getMaxAttendees());
        $this->assertSame('https://img.test/event.jpg', $e->getImageUrl());
    }

    public function testDefaultValues(): void
    {
        $e = new CommunityEvent();
        $this->assertSame(CommunityEvent::TYPE_MEETUP, $e->getEventType());
        $this->assertSame(CommunityEvent::STATUS_UPCOMING, $e->getStatus());
        $this->assertSame(0, $e->getMaxAttendees());
        $this->assertSame(0, $e->getCurrentAttendees());
        $this->assertFalse($e->isOnline());
    }

    public function testStatusTransition(): void
    {
        $e = new CommunityEvent();
        $e->setStatus(CommunityEvent::STATUS_ONGOING);
        $this->assertSame(CommunityEvent::STATUS_ONGOING, $e->getStatus());

        $e->setStatus(CommunityEvent::STATUS_COMPLETED);
        $this->assertSame(CommunityEvent::STATUS_COMPLETED, $e->getStatus());
    }

    public function testOnlineEvent(): void
    {
        $e = new CommunityEvent();
        $e->setIsOnline(true);
        $e->setOnlineLink('https://meet.test/abc');

        $this->assertTrue($e->isOnline());
        $this->assertSame('https://meet.test/abc', $e->getOnlineLink());
    }

    public function testTimestampSetInConstructor(): void
    {
        $e = new CommunityEvent();
        $this->assertInstanceOf(\DateTimeImmutable::class, $e->getCreatedAt());
    }

    public function testRsvpsCollection(): void
    {
        $e = new CommunityEvent();
        $this->assertCount(0, $e->getRsvps());
    }

    public function testEventTypes(): void
    {
        $this->assertSame('MEETUP', CommunityEvent::TYPE_MEETUP);
        $this->assertSame('WEBINAR', CommunityEvent::TYPE_WEBINAR);
        $this->assertSame('WORKSHOP', CommunityEvent::TYPE_WORKSHOP);
        $this->assertSame('CONFERENCE', CommunityEvent::TYPE_CONFERENCE);
        $this->assertSame('NETWORKING', CommunityEvent::TYPE_NETWORKING);
    }
}

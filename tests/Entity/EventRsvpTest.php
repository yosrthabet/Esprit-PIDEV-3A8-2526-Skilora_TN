<?php

namespace App\Tests\Entity;

use App\Entity\CommunityEvent;
use App\Entity\EventRsvp;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EventRsvpTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('attendee');
        $u->setEmail('attendee@test.com');
        $u->setFullName('Attendee');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new EventRsvp())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $r = new EventRsvp();
        $event = new CommunityEvent();
        $user = $this->makeUser();

        $r->setEvent($event);
        $r->setUser($user);
        $r->setStatus(EventRsvp::STATUS_MAYBE);

        $this->assertSame($event, $r->getEvent());
        $this->assertSame($user, $r->getUser());
        $this->assertSame(EventRsvp::STATUS_MAYBE, $r->getStatus());
    }

    public function testDefaultStatus(): void
    {
        $r = new EventRsvp();
        $this->assertSame(EventRsvp::STATUS_GOING, $r->getStatus());
    }

    public function testTimestampSetInConstructor(): void
    {
        $r = new EventRsvp();
        $this->assertInstanceOf(\DateTimeImmutable::class, $r->getRsvpDate());
    }
}

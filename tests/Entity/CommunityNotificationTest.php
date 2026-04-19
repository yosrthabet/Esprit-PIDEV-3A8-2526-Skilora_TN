<?php

namespace App\Tests\Entity;

use App\Entity\CommunityNotification;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CommunityNotificationTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('notified');
        $u->setEmail('notified@test.com');
        $u->setFullName('Notified');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new CommunityNotification())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $n = new CommunityNotification();
        $user = $this->makeUser();

        $n->setUser($user);
        $n->setType('LIKE');
        $n->setTitle('New Like');
        $n->setMessage('Someone liked your post');
        $n->setIcon('❤️');

        $this->assertSame($user, $n->getUser());
        $this->assertSame('LIKE', $n->getType());
        $this->assertSame('New Like', $n->getTitle());
        $this->assertSame('Someone liked your post', $n->getMessage());
        $this->assertSame('❤️', $n->getIcon());
    }

    public function testDefaultValues(): void
    {
        $n = new CommunityNotification();
        $this->assertSame('INFO', $n->getType());
        $this->assertFalse($n->isRead());
        $this->assertSame('🔔', $n->getIcon());
    }

    public function testReadStatus(): void
    {
        $n = new CommunityNotification();
        $n->setIsRead(true);
        $this->assertTrue($n->isRead());
    }

    public function testReferenceFields(): void
    {
        $n = new CommunityNotification();
        $n->setReferenceType('post');
        $n->setReferenceId(42);

        $this->assertSame('post', $n->getReferenceType());
        $this->assertSame(42, $n->getReferenceId());
    }

    public function testTimestampSetInConstructor(): void
    {
        $n = new CommunityNotification();
        $this->assertInstanceOf(\DateTimeImmutable::class, $n->getCreatedAt());
    }
}

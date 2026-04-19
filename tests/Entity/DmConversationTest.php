<?php

namespace App\Tests\Entity;

use App\Entity\DmConversation;
use App\Entity\DmMessage;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class DmConversationTest extends TestCase
{
    private function makeUser(string $name): User
    {
        $u = new User();
        $u->setUsername($name);
        $u->setEmail($name . '@test.com');
        $u->setFullName(ucfirst($name));
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new DmConversation())->getId());
    }

    public function testForUsersFactory(): void
    {
        $a = $this->makeUser('alice');
        $b = $this->makeUser('bob');
        $conv = DmConversation::forUsers($a, $b);

        $this->assertNotNull($conv->getParticipantLow());
        $this->assertNotNull($conv->getParticipantHigh());
        $this->assertInstanceOf(\DateTimeImmutable::class, $conv->getCreatedAt());
    }

    public function testInvolves(): void
    {
        $a = $this->makeUser('alice');
        $b = $this->makeUser('bob');
        $c = $this->makeUser('charlie');

        // Set distinct IDs via reflection (involves() compares by ID)
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($a, 1);
        $ref->setValue($b, 2);
        $ref->setValue($c, 3);

        $conv = DmConversation::forUsers($a, $b);

        $this->assertTrue($conv->involves($a));
        $this->assertTrue($conv->involves($b));
        $this->assertFalse($conv->involves($c));
    }

    public function testAddMessage(): void
    {
        $a = $this->makeUser('alice');
        $b = $this->makeUser('bob');
        $conv = DmConversation::forUsers($a, $b);

        $msg = new DmMessage();
        $msg->setSender($a);
        $msg->setBody('Hello Bob!');
        $conv->addMessage($msg);

        $this->assertCount(1, $conv->getMessages());
        $this->assertSame($conv, $msg->getConversation());
    }

    public function testEmptyMessagesCollection(): void
    {
        $conv = new DmConversation();
        $this->assertCount(0, $conv->getMessages());
    }
}

<?php

namespace App\Tests\Entity;

use App\Entity\MemberInvitation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class MemberInvitationTest extends TestCase
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
        $this->assertNull((new MemberInvitation())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $inv = new MemberInvitation();
        $inviter = $this->makeUser('alice');
        $invitee = $this->makeUser('bob');
        $inv->setInviter($inviter);
        $inv->setInvitee($invitee);
        $inv->setNote('Join our team!');

        $this->assertSame($inviter, $inv->getInviter());
        $this->assertSame($invitee, $inv->getInvitee());
        $this->assertSame('Join our team!', $inv->getNote());
    }

    public function testDefaultStatusIsPending(): void
    {
        $inv = new MemberInvitation();
        $this->assertSame('pending', $inv->getStatus());
        $this->assertTrue($inv->isPending());
    }

    public function testStatusChange(): void
    {
        $inv = new MemberInvitation();
        $inv->setStatus('accepted');
        $this->assertSame('accepted', $inv->getStatus());
        $this->assertFalse($inv->isPending());
    }

    public function testRespondedAt(): void
    {
        $inv = new MemberInvitation();
        $this->assertNull($inv->getRespondedAt());
        $now = new \DateTimeImmutable();
        $inv->setRespondedAt($now);
        $this->assertSame($now, $inv->getRespondedAt());
    }
}

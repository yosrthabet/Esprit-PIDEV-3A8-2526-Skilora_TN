<?php

namespace App\Tests\Entity;

use App\Entity\CommunityGroup;
use App\Entity\GroupMember;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class GroupMemberTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('member');
        $u->setEmail('member@test.com');
        $u->setFullName('Member');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new GroupMember())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $m = new GroupMember();
        $group = new CommunityGroup();
        $user = $this->makeUser();

        $m->setGroup($group);
        $m->setUser($user);
        $m->setRole(GroupMember::ROLE_MODERATOR);

        $this->assertSame($group, $m->getGroup());
        $this->assertSame($user, $m->getUser());
        $this->assertSame(GroupMember::ROLE_MODERATOR, $m->getRole());
    }

    public function testDefaultRole(): void
    {
        $m = new GroupMember();
        $this->assertSame(GroupMember::ROLE_MEMBER, $m->getRole());
    }

    public function testTimestampSetInConstructor(): void
    {
        $m = new GroupMember();
        $this->assertInstanceOf(\DateTimeImmutable::class, $m->getJoinedAt());
    }

    public function testRoleConstants(): void
    {
        $this->assertSame('ADMIN', GroupMember::ROLE_ADMIN);
        $this->assertSame('MODERATOR', GroupMember::ROLE_MODERATOR);
        $this->assertSame('MEMBER', GroupMember::ROLE_MEMBER);
    }
}

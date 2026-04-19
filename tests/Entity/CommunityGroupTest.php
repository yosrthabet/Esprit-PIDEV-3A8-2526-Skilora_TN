<?php

namespace App\Tests\Entity;

use App\Entity\CommunityGroup;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CommunityGroupTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('creator');
        $u->setEmail('creator@test.com');
        $u->setFullName('Creator');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new CommunityGroup())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $g = new CommunityGroup();
        $creator = $this->makeUser();

        $g->setName('PHP Devs');
        $g->setDescription('A group for PHP developers');
        $g->setCategory('Programming');
        $g->setCoverImageUrl('https://img.test/group.jpg');
        $g->setCreator($creator);

        $this->assertSame('PHP Devs', $g->getName());
        $this->assertSame('A group for PHP developers', $g->getDescription());
        $this->assertSame('Programming', $g->getCategory());
        $this->assertSame('https://img.test/group.jpg', $g->getCoverImageUrl());
        $this->assertSame($creator, $g->getCreator());
    }

    public function testDefaultValues(): void
    {
        $g = new CommunityGroup();
        $this->assertSame(1, $g->getMemberCount());
        $this->assertTrue($g->isPublic());
    }

    public function testMemberCount(): void
    {
        $g = new CommunityGroup();
        $g->setMemberCount(42);
        $this->assertSame(42, $g->getMemberCount());
    }

    public function testPrivateGroup(): void
    {
        $g = new CommunityGroup();
        $g->setIsPublic(false);
        $this->assertFalse($g->isPublic());
    }

    public function testTimestampSetInConstructor(): void
    {
        $g = new CommunityGroup();
        $this->assertInstanceOf(\DateTimeImmutable::class, $g->getCreatedAt());
    }

    public function testMembersCollection(): void
    {
        $g = new CommunityGroup();
        $this->assertCount(0, $g->getMembers());
    }

    public function testCoverImageUrlTrimsWhitespace(): void
    {
        $g = new CommunityGroup();
        $g->setCoverImageUrl('  https://img.test/group.jpg  ');
        $this->assertSame('https://img.test/group.jpg', $g->getCoverImageUrl());
    }

    public function testCoverImageUrlEmptyBecomesNull(): void
    {
        $g = new CommunityGroup();
        $g->setCoverImageUrl('   ');
        $this->assertNull($g->getCoverImageUrl());
    }
}

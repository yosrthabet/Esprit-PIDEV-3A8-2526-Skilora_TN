<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ProfileTest extends TestCase
{
    private function createUser(): User
    {
        $u = new User();
        $u->setUsername('profuser');
        $u->setEmail('prof@test.com');
        $u->setFullName('Prof User');
        $u->setRole('USER');
        $u->setPassword('pass');

        return $u;
    }

    private function createProfile(array $o = []): Profile
    {
        $p = new Profile();
        $p->setUser($o['user'] ?? $this->createUser());
        $p->setFirstName($o['firstName'] ?? 'Jane');
        $p->setLastName($o['lastName'] ?? 'Doe');
        $p->setPhone($o['phone'] ?? '+21612345678');
        $p->setLocation($o['location'] ?? 'Tunis');
        $p->setHeadline($o['headline'] ?? 'Developer');
        $p->setBio($o['bio'] ?? 'A great dev');
        $p->setWebsite($o['website'] ?? 'https://example.com');

        return $p;
    }

    public function testGettersReturnSetValues(): void
    {
        $p = $this->createProfile();

        $this->assertSame('Jane', $p->getFirstName());
        $this->assertSame('Doe', $p->getLastName());
        $this->assertSame('+21612345678', $p->getPhone());
        $this->assertSame('Tunis', $p->getLocation());
        $this->assertSame('Developer', $p->getHeadline());
        $this->assertSame('A great dev', $p->getBio());
        $this->assertSame('https://example.com', $p->getWebsite());
        $this->assertNotNull($p->getUser());
    }

    public function testNewProfileIdIsNull(): void
    {
        $p = new Profile();
        $this->assertNull($p->getId());
    }

    public function testFullName(): void
    {
        $p = $this->createProfile();
        $this->assertSame('Jane Doe', $p->getFullName());
    }

    public function testNullableFields(): void
    {
        $p = new Profile();
        $this->assertNull($p->getFirstName());
        $this->assertNull($p->getLastName());
        $this->assertNull($p->getPhone());
        $this->assertNull($p->getCvUrl());
        $this->assertNull($p->getPhotoUrl());
        $this->assertNull($p->getBirthDate());
    }

    public function testSettersReturnSelf(): void
    {
        $p = new Profile();
        $this->assertSame($p, $p->setFirstName('A'));
        $this->assertSame($p, $p->setLastName('B'));
        $this->assertSame($p, $p->setPhone('123'));
        $this->assertSame($p, $p->setLocation('X'));
        $this->assertSame($p, $p->setHeadline('H'));
        $this->assertSame($p, $p->setBio('B'));
        $this->assertSame($p, $p->setWebsite('W'));
        $this->assertSame($p, $p->setCvUrl('cv'));
        $this->assertSame($p, $p->setPhotoUrl('ph'));
    }

    public function testBirthDate(): void
    {
        $p = new Profile();
        $date = new \DateTime('1990-01-15');
        $p->setBirthDate($date);
        $this->assertSame($date, $p->getBirthDate());
    }
}

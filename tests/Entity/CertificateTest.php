<?php

namespace App\Tests\Entity;

use App\Entity\Certificate;
use App\Entity\Formation;
use App\Entity\User;
use App\Enum\FormationLevel;
use PHPUnit\Framework\TestCase;

class CertificateTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('certuser');
        $u->setEmail('c@test.com');
        $u->setFullName('Cert User');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    private function makeFormation(): Formation
    {
        $f = new Formation();
        $f->setTitle('Cert Course');
        $f->setCategory('DEVELOPMENT');
        $f->setDuration(20);
        $f->setLessonsCount(8);
        $f->setLevel(FormationLevel::ADVANCED);

        return $f;
    }

    public function testNewCertificateIdIsNull(): void
    {
        $this->assertNull((new Certificate())->getId());
    }

    public function testSetUserAndFormation(): void
    {
        $c = new Certificate();
        $c->setUser($this->makeUser());
        $c->setFormation($this->makeFormation());

        $this->assertSame('certuser', $c->getUser()->getUsername());
        $this->assertSame('Cert Course', $c->getFormation()->getTitle());
    }

    public function testIssuedAt(): void
    {
        $c = new Certificate();
        $now = new \DateTimeImmutable();
        $c->setIssuedAt($now);
        $this->assertSame($now, $c->getIssuedAt());
    }

    public function testNullDefaults(): void
    {
        $c = new Certificate();
        $this->assertNull($c->getUser());
        $this->assertNull($c->getFormation());
        $this->assertNull($c->getIssuedAt());
    }
}

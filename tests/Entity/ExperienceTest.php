<?php

namespace App\Tests\Entity;

use App\Entity\Experience;
use App\Entity\Profile;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ExperienceTest extends TestCase
{
    private function makeProfile(): Profile
    {
        $u = new User();
        $u->setUsername('expuser');
        $u->setEmail('exp@test.com');
        $u->setFullName('Exp User');
        $u->setRole('USER');
        $u->setPassword('p');

        $p = new Profile();
        $p->setUser($u);

        return $p;
    }

    public function testNewExperienceIdIsNull(): void
    {
        $this->assertNull((new Experience())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $e = new Experience();
        $e->setProfile($this->makeProfile());
        $e->setCompany('Sofrecom');
        $e->setPosition('Developer');
        $start = new \DateTime('2020-01-01');
        $end = new \DateTime('2023-12-31');
        $e->setStartDate($start);
        $e->setEndDate($end);
        $e->setDescription('Built stuff');
        $e->setCurrentJob(false);

        $this->assertSame('Sofrecom', $e->getCompany());
        $this->assertSame('Developer', $e->getPosition());
        $this->assertSame($start, $e->getStartDate());
        $this->assertSame($end, $e->getEndDate());
        $this->assertSame('Built stuff', $e->getDescription());
        $this->assertFalse($e->isCurrentJob());
    }

    public function testNullDefaults(): void
    {
        $e = new Experience();
        $this->assertNull($e->getCompany());
        $this->assertNull($e->getPosition());
        $this->assertNull($e->getStartDate());
        $this->assertNull($e->getEndDate());
        $this->assertNull($e->getDescription());
    }
}

<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\Skill;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SkillTest extends TestCase
{
    private function makeProfile(): Profile
    {
        $u = new User();
        $u->setUsername('skilluser');
        $u->setEmail('sk@test.com');
        $u->setFullName('Skill User');
        $u->setRole('USER');
        $u->setPassword('p');

        $p = new Profile();
        $p->setUser($u);

        return $p;
    }

    public function testNewSkillIdIsNull(): void
    {
        $this->assertNull((new Skill())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $s = new Skill();
        $s->setProfile($this->makeProfile());
        $s->setSkillName('PHP');
        $s->setProficiencyLevel('ADVANCED');
        $s->setYearsExperience(5);
        $s->setVerified(true);

        $this->assertSame('PHP', $s->getSkillName());
        $this->assertSame('ADVANCED', $s->getProficiencyLevel());
        $this->assertSame(5, $s->getYearsExperience());
        $this->assertTrue($s->isVerified());
        $this->assertNotNull($s->getProfile());
    }

    public function testNullDefaults(): void
    {
        $s = new Skill();
        $this->assertNull($s->getSkillName());
        $this->assertNull($s->getProficiencyLevel());
        $this->assertNull($s->getYearsExperience());
    }
}

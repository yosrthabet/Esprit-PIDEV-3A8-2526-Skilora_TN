<?php

namespace App\Tests\Integration;

use App\Entity\Profile;
use App\Entity\Skill;
use App\Entity\Experience;

class ProfileCrudTest extends DatabaseTestCase
{
    private function createProfile(): Profile
    {
        $user = $this->createTestUser();
        $p = new Profile();
        $p->setUser($user);
        $p->setFirstName('Jane');
        $p->setLastName('Doe');
        $p->setPhone('+21612345678');
        $p->setLocation('Tunis');
        $this->em->persist($p);
        $this->em->flush();

        return $p;
    }

    public function testCreateProfile(): void
    {
        $p = $this->createProfile();
        $this->assertNotNull($p->getId());
        $this->assertSame('Jane', $p->getFirstName());
    }

    public function testReadProfile(): void
    {
        $p = $this->createProfile();
        $id = $p->getId();
        $this->em->clear();

        $found = $this->em->find(Profile::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Doe', $found->getLastName());
    }

    public function testUpdateProfile(): void
    {
        $p = $this->createProfile();
        $p->setHeadline('Senior Developer');
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(Profile::class, $p->getId());
        $this->assertSame('Senior Developer', $found->getHeadline());
    }

    public function testDeleteProfile(): void
    {
        $p = $this->createProfile();
        $id = $p->getId();
        $this->em->remove($p);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(Profile::class, $id));
    }

    public function testCreateSkill(): void
    {
        $p = $this->createProfile();
        $s = new Skill();
        $s->setProfile($p);
        $s->setSkillName('PHP');
        $s->setProficiencyLevel('ADVANCED');
        $s->setYearsExperience(5);
        $this->em->persist($s);
        $this->em->flush();

        $this->assertNotNull($s->getId());
    }

    public function testCreateExperience(): void
    {
        $p = $this->createProfile();
        $e = new Experience();
        $e->setProfile($p);
        $e->setCompany('Sofrecom');
        $e->setPosition('Developer');
        $e->setStartDate(new \DateTime('2020-01-01'));
        $e->setCurrentJob(true);
        $this->em->persist($e);
        $this->em->flush();

        $this->assertNotNull($e->getId());
    }
}

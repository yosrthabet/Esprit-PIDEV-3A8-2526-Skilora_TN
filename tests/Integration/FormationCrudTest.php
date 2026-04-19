<?php

namespace App\Tests\Integration;

use App\Entity\Certificate;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Enum\FormationLevel;

class FormationCrudTest extends DatabaseTestCase
{
    private function createFormation(): Formation
    {
        $f = new Formation();
        $f->setTitle('Test Formation ' . bin2hex(random_bytes(3)));
        $f->setDescription('Integration test formation');
        $f->setCategory('DEVELOPMENT');
        $f->setDuration(20);
        $f->setLessonsCount(5);
        $f->setLevel(FormationLevel::BEGINNER);
        $f->setPrice(0.0);
        $f->setCurrency('TND');
        $f->setStatus('ACTIVE');
        $f->setIsFree(true);
        $this->em->persist($f);
        $this->em->flush();

        return $f;
    }

    public function testCreateFormation(): void
    {
        $f = $this->createFormation();
        $this->assertNotNull($f->getId());
    }

    public function testReadFormation(): void
    {
        $f = $this->createFormation();
        $id = $f->getId();
        $this->em->clear();

        $found = $this->em->find(Formation::class, $id);
        $this->assertNotNull($found);
        $this->assertStringStartsWith('Test Formation', $found->getTitle());
    }

    public function testUpdateFormation(): void
    {
        $f = $this->createFormation();
        $f->setTitle('Updated Formation');
        $f->setLevel(FormationLevel::ADVANCED);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(Formation::class, $f->getId());
        $this->assertSame('Updated Formation', $found->getTitle());
        $this->assertSame(FormationLevel::ADVANCED, $found->getLevel());
    }

    public function testDeleteFormation(): void
    {
        $f = $this->createFormation();
        $id = $f->getId();
        $this->em->remove($f);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(Formation::class, $id));
    }

    public function testEnrollment(): void
    {
        $user = $this->createTestUser();
        $formation = $this->createFormation();

        $e = new Enrollment();
        $e->setUser($user);
        $e->setFormation($formation);
        $e->setEnrolledAt(new \DateTimeImmutable());
        $this->em->persist($e);
        $this->em->flush();

        $this->assertNotNull($e->getId());
        $this->assertFalse($e->isCompleted());
    }

    public function testCertificate(): void
    {
        $user = $this->createTestUser();
        $formation = $this->createFormation();

        $c = new Certificate();
        $c->setUser($user);
        $c->setFormation($formation);
        $c->setIssuedAt(new \DateTimeImmutable());
        $this->em->persist($c);
        $this->em->flush();

        $this->assertNotNull($c->getId());
    }

    public function testEnrollmentCompletion(): void
    {
        $user = $this->createTestUser();
        $formation = $this->createFormation();

        $e = new Enrollment();
        $e->setUser($user);
        $e->setFormation($formation);
        $e->setEnrolledAt(new \DateTimeImmutable());
        $this->em->persist($e);
        $this->em->flush();

        $e->setIsCompleted(true);
        $e->setCompletedAt(new \DateTimeImmutable());
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(Enrollment::class, $e->getId());
        $this->assertTrue($found->isCompleted());
        $this->assertNotNull($found->getCompletedAt());
    }
}

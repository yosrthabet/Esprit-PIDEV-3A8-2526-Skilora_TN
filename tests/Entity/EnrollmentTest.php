<?php

namespace App\Tests\Entity;

use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\User;
use App\Enum\FormationLevel;
use PHPUnit\Framework\TestCase;

class EnrollmentTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('student');
        $u->setEmail('s@test.com');
        $u->setFullName('Student');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    private function makeFormation(): Formation
    {
        $f = new Formation();
        $f->setTitle('Test Course');
        $f->setCategory('DEVELOPMENT');
        $f->setDuration(10);
        $f->setLessonsCount(5);
        $f->setLevel(FormationLevel::BEGINNER);

        return $f;
    }

    public function testNewEnrollmentIdIsNull(): void
    {
        $this->assertNull((new Enrollment())->getId());
    }

    public function testSetUserAndFormation(): void
    {
        $e = new Enrollment();
        $e->setUser($this->makeUser());
        $e->setFormation($this->makeFormation());

        $this->assertSame('student', $e->getUser()->getUsername());
        $this->assertSame('Test Course', $e->getFormation()->getTitle());
    }

    public function testDefaultNotCompleted(): void
    {
        $e = new Enrollment();
        $this->assertFalse($e->isCompleted());
        $this->assertNull($e->getCompletedAt());
    }

    public function testMarkCompleted(): void
    {
        $e = new Enrollment();
        $e->setIsCompleted(true);
        $now = new \DateTimeImmutable();
        $e->setCompletedAt($now);

        $this->assertTrue($e->isCompleted());
        $this->assertSame($now, $e->getCompletedAt());
    }

    public function testEnrolledAt(): void
    {
        $e = new Enrollment();
        $now = new \DateTimeImmutable();
        $e->setEnrolledAt($now);
        $this->assertSame($now, $e->getEnrolledAt());
    }
}

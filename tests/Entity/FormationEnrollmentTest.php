<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Formation\EnrollmentStatus;
use App\Formation\Entity\Certificate;
use App\Formation\Entity\Enrollment;
use App\Formation\Entity\Formation;
use App\Formation\Entity\FormationModule;
use App\Formation\Entity\LessonProgress;
use PHPUnit\Framework\TestCase;

final class FormationEnrollmentTest extends TestCase
{
    public function testEnrollmentProgressAndCertificateState(): void
    {
        $user = (new User())->setUsername('learner')->setRole('USER');
        $formation = (new Formation())->setTitle('PHP')->setCategory('Web');
        $module = (new FormationModule())->setTitle('Classes')->setFormation($formation);
        $enrollment = (new Enrollment())->setUser($user)->setFormation($formation);
        $progress = (new LessonProgress())
            ->setModule($module)
            ->setProgressPercent(120);

        $enrollment->addLessonProgress($progress)->complete();
        $certificate = (new Certificate())
            ->setEnrollment($enrollment)
            ->setVerificationId('verify-123');

        self::assertSame(EnrollmentStatus::COMPLETED, $enrollment->getStatus());
        self::assertSame(100, $progress->getProgressPercent());
        self::assertTrue($progress->isComplete());
        self::assertSame($certificate, $enrollment->getCertificate());
        self::assertSame('verify-123', $certificate->getVerificationId());
    }
}

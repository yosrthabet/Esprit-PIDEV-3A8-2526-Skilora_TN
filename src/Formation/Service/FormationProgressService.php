<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Formation\Entity\Certificate;
use App\Formation\Entity\Enrollment;
use App\Formation\Entity\FormationModule;
use App\Formation\Entity\LessonProgress;
use App\Formation\EnrollmentStatus;
use App\Formation\Repository\CertificateRepository;
use App\Formation\Repository\LessonProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class FormationProgressService
{
    public function __construct(
        private readonly LessonProgressRepository $progressRepository,
        private readonly CertificateRepository $certificateRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function recordProgress(Enrollment $enrollment, FormationModule $module, int $progressPercent): LessonProgress
    {
        if ($enrollment->getStatus() === EnrollmentStatus::CANCELLED) {
            throw new \RuntimeException('Cancelled enrollments cannot be updated.');
        }

        if ($module->getFormation() !== $enrollment->getFormation()) {
            throw new \RuntimeException('Module does not belong to the enrolled formation.');
        }

        $progress = $this->progressRepository->findOneForEnrollmentAndModule($enrollment, $module);
        if ($progress === null) {
            $progress = (new LessonProgress())
                ->setModule($module);
            $enrollment->addLessonProgress($progress);
            $this->entityManager->persist($progress);
        }

        $progress->setProgressPercent($progressPercent);
        if ($this->getCompletionPercent($enrollment) === 100) {
            $this->completeEnrollment($enrollment);
        }

        $this->entityManager->flush();

        return $progress;
    }

    public function getCompletionPercent(Enrollment $enrollment): int
    {
        $totalModules = $enrollment->getFormation()->getModules()->count();
        if ($totalModules === 0) {
            return 0;
        }

        $completedModules = 0;
        foreach ($enrollment->getLessonProgress() as $progress) {
            if ($progress->isComplete()) {
                $completedModules++;
            }
        }

        return (int) floor(($completedModules / $totalModules) * 100);
    }

    public function issueCertificate(Enrollment $enrollment): Certificate
    {
        $existing = $this->certificateRepository->findOneForEnrollment($enrollment);
        if ($existing !== null) {
            return $existing;
        }

        if ($enrollment->getStatus() !== EnrollmentStatus::COMPLETED) {
            throw new \RuntimeException('Only completed enrollments can receive certificates.');
        }

        $certificate = (new Certificate())
            ->setEnrollment($enrollment)
            ->setVerificationId($this->generateVerificationId());

        $this->entityManager->persist($certificate);
        $this->entityManager->flush();

        return $certificate;
    }

    private function completeEnrollment(Enrollment $enrollment): void
    {
        if ($enrollment->getStatus() !== EnrollmentStatus::COMPLETED) {
            $enrollment->complete();
        }
    }

    private function generateVerificationId(): string
    {
        do {
            $verificationId = bin2hex(random_bytes(16));
        } while ($this->certificateRepository->findOneByVerificationId($verificationId) !== null);

        return $verificationId;
    }
}

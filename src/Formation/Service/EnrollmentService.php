<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Entity\User;
use App\Formation\Entity\Enrollment;
use App\Formation\Entity\Formation;
use App\Formation\EnrollmentStatus;
use App\Formation\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;

class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function enroll(User $user, Formation $formation): Enrollment
    {
        $existing = $this->enrollmentRepository->findOneForUserAndFormation($user, $formation);
        if ($existing !== null) {
            return $existing;
        }

        if (!$formation->isPublished()) {
            throw new \RuntimeException('Only published formations can be enrolled.');
        }

        $enrollment = (new Enrollment())
            ->setUser($user)
            ->setFormation($formation);

        $this->entityManager->persist($enrollment);
        $this->entityManager->flush();

        return $enrollment;
    }

    public function cancel(Enrollment $enrollment): void
    {
        if ($enrollment->getStatus() === EnrollmentStatus::COMPLETED) {
            throw new \RuntimeException('Completed enrollments cannot be cancelled.');
        }

        $enrollment->cancel();
        $this->entityManager->flush();
    }
}

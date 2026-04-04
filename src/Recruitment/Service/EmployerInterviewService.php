<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\JobInterview;
use App\Recruitment\InterviewFormat;
use App\Recruitment\InterviewLifecycle;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\JobInterviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class EmployerInterviewService
{
    public function __construct(
        private readonly ApplicationsTableGateway $applicationsTableGateway,
        private readonly EmployerApplicationService $employerApplicationService,
        private readonly JobInterviewRepository $jobInterviewRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function scheduleOrUpdate(
        User $employer,
        int $applicationId,
        \DateTimeImmutable $scheduledAt,
        string $format,
        ?string $location,
        string $lifecycleStatus,
    ): JobInterview {
        $row = $this->applicationsTableGateway->fetchById($applicationId);
        if ($row === null) {
            throw new BadRequestHttpException('Seules les candidatures marquées « Accepté » peuvent recevoir un entretien planifié.');
        }
        $status = strtoupper(trim((string) ($row['status'] ?? '')));
        if ($status !== ApplicationStatus::ACCEPTED) {
            throw new BadRequestHttpException('Seules les candidatures marquées « Accepté » peuvent recevoir un entretien planifié.');
        }

        $this->employerApplicationService->assertEmployerManagesApplication($employer, $applicationId);

        if (!InterviewFormat::isValid($format)) {
            throw new BadRequestHttpException('Type d’entretien invalide.');
        }

        if (!InterviewLifecycle::isValid($lifecycleStatus)) {
            throw new BadRequestHttpException('Statut d’entretien invalide.');
        }

        $application = $this->entityManager->find(Application::class, $applicationId);
        if ($application === null) {
            throw new BadRequestHttpException('Candidature introuvable.');
        }

        $interview = $this->jobInterviewRepository->findOneByApplicationId($applicationId);
        if ($interview === null) {
            $interview = new JobInterview();
            $interview->setApplication($application);
        }

        $interview->setScheduledAt($scheduledAt);
        $interview->setFormat($format);
        $interview->setLocation($location);
        $interview->setLifecycleStatus($lifecycleStatus);
        $interview->touchUpdated();

        $this->entityManager->persist($interview);
        $this->entityManager->flush();

        return $interview;
    }
}

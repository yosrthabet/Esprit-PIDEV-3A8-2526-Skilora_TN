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
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EmployerInterviewService
{
    public function __construct(
        private readonly ApplicationsTableGateway $applicationsTableGateway,
        private readonly EmployerApplicationService $employerApplicationService,
        private readonly JobInterviewRepository $jobInterviewRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly InterviewsTableColumnsEnsurer $interviewsTableColumnsEnsurer,
        private readonly ValidatorInterface $validator,
        private readonly InterviewWhatsAppNotifier $interviewWhatsAppNotifier,
    ) {
    }

    public function scheduleOrUpdate(
        User $employer,
        int $applicationId,
        \DateTimeImmutable $scheduledAt,
        string $format,
        ?string $location,
        ?string $notes,
    ): InterviewScheduleResult {
        $this->interviewsTableColumnsEnsurer->ensureDoctrineColumns();

        $row = $this->applicationsTableGateway->fetchById($applicationId);
        if ($row === null) {
            throw new BadRequestHttpException('Seules les candidatures marquées « Accepté » peuvent recevoir un entretien planifié.');
        }
        $status = strtoupper(trim((string) ($row['status'] ?? '')));
        if ($status !== ApplicationStatus::ACCEPTED) {
            throw new BadRequestHttpException('Seules les candidatures marquées « Accepté » peuvent recevoir un entretien planifié.');
        }

        $this->employerApplicationService->assertEmployerManagesApplication($employer, $applicationId);

        if ($format === InterviewFormat::ONLINE) {
            $location = null;
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
        $interview->setNotes($notes);
        $interview->setLifecycleStatus(InterviewLifecycle::SCHEDULED);

        $violations = $this->validator->validate($interview, null, [JobInterview::VALIDATION_GROUP_SCHEDULE]);
        if (\count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[$violation->getMessage()] = true;
            }

            throw new BadRequestHttpException(implode(' ', array_keys($messages)));
        }

        $this->entityManager->persist($interview);
        $this->entityManager->flush();
        $waResult = $this->interviewWhatsAppNotifier->notifyCandidateInterviewScheduled($interview);

        return new InterviewScheduleResult($interview, $waResult);
    }
}

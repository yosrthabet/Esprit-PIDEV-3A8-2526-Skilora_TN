<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\ApplicationStatus;
use App\Enum\Currency;
use App\Enum\HireOfferStatus;
use App\Enum\InterviewFormat;
use App\Enum\InterviewStatus;
use App\Finance\Service\ContractService;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\HireOffer;
use App\Recruitment\Entity\JobInterview;
use App\Recruitment\Repository\JobInterviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\InputBag;

class ApplicationPipelineService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JobInterviewRepository $jobInterviewRepository,
        private readonly MeetingLinkFactory $meetingLinkFactory,
        private readonly ContractService $contractService,
    ) {
    }

    public function assertEmployerManages(User $employer, Application $application): void
    {
        if ($application->getJobOffer()->getCompany()?->getOwner()->getId() !== $employer->getId()) {
            throw new \RuntimeException('You cannot manage this application.');
        }
    }

    public function move(User $employer, Application $application, ApplicationStatus $status): void
    {
        $this->assertEmployerManages($employer, $application);
        $application->setStatus($status);
        $this->entityManager->flush();
    }

    /** @param InputBag<bool|float|int|string> $data */
    public function scheduleInterview(User $employer, Application $application, InputBag $data): JobInterview
    {
        $this->assertEmployerManages($employer, $application);
        $date = $data->getString('scheduled_at');
        $scheduledAt = $date !== '' ? new \DateTimeImmutable($date) : new \DateTimeImmutable('+1 day 10:00');
        $format = InterviewFormat::tryFrom($data->getString('format')) ?? InterviewFormat::ONLINE;
        $provider = $data->getString('meeting_provider') ?: 'jitsi';
        $meetingUrl = trim($data->getString('meeting_url')) ?: null;

        if ($format === InterviewFormat::ONLINE && $meetingUrl === null && $provider === 'jitsi') {
            $meetingUrl = $this->meetingLinkFactory->generateJitsiLink((int) $application->getId());
        }

        $interview = $this->jobInterviewRepository->findOneForApplication($application) ?? new JobInterview();
        $interview
            ->setApplication($application)
            ->scheduleAt($scheduledAt)
            ->setDurationMinutes($data->getInt('duration_minutes', 30))
            ->setFormat($format)
            ->setMeetingProvider($provider)
            ->setMeetingUrl($meetingUrl)
            ->setLocation(trim($data->getString('location')) ?: null)
            ->setNotes(trim($data->getString('notes')) ?: null)
            ->setStatus(InterviewStatus::SCHEDULED);
        $application->setStatus(ApplicationStatus::INTERVIEW);
        $this->entityManager->persist($interview);
        $this->notifyCandidate(
            $application,
            'interview_scheduled',
            'Interview scheduled',
            sprintf(
                'Your interview for "%s" is scheduled on %s. %s',
                $application->getJobOffer()->getTitle(),
                $scheduledAt->format('M d, Y H:i'),
                $meetingUrl !== null ? 'Meeting link is available in your application.' : 'Check your application for details.',
            ),
            '📅',
        );
        $this->entityManager->flush();

        return $interview;
    }

    /** @param InputBag<bool|float|int|string> $data */
    public function sendHireOffer(User $employer, Application $application, InputBag $data): HireOffer
    {
        $this->assertEmployerManages($employer, $application);
        $offer = new HireOffer();
        $startDate = $data->getString('start_date');
        $offer
            ->setApplication($application)
            ->setSalaryOffered(trim($data->getString('salary_offered')) ?: null)
            ->setCurrency(Currency::tryFrom($data->getString('currency')) ?? Currency::TND)
            ->setContractType(trim($data->getString('contract_type')) ?: null)
            ->setStartDate($startDate !== '' ? new \DateTimeImmutable($startDate) : null)
            ->setBenefits(trim($data->getString('benefits')) ?: null);
        $application->setStatus(ApplicationStatus::OFFER);
        $this->entityManager->persist($offer);
        $this->notifyCandidate(
            $application,
            'hire_offer',
            'New hire offer',
            sprintf('You received a hire offer for "%s".', $application->getJobOffer()->getTitle()),
            '💼',
        );
        $this->entityManager->flush();

        return $offer;
    }

    public function acceptOffer(User $candidate, HireOffer $offer): void
    {
        if ($offer->getApplication()->getCandidate()->getId() !== $candidate->getId()) {
            throw new \RuntimeException('You cannot respond to this offer.');
        }
        if ($offer->getStatus() !== HireOfferStatus::PENDING) {
            throw new \RuntimeException('This offer is no longer pending.');
        }
        $offer->accept();
        $offer->getApplication()->setStatus(ApplicationStatus::OFFER);
        $this->entityManager->flush();
        $this->contractService->createFromAcceptedOffer($offer);
    }

    public function rejectOffer(User $candidate, HireOffer $offer): void
    {
        if ($offer->getApplication()->getCandidate()->getId() !== $candidate->getId()) {
            throw new \RuntimeException('You cannot respond to this offer.');
        }
        $offer->reject();
        $this->entityManager->flush();
    }

    private function notifyCandidate(Application $application, string $type, string $title, string $message, string $icon): void
    {
        $notification = (new Notification())
            ->setUser($application->getCandidate())
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setIcon($icon)
            ->setReferenceType('application')
            ->setReferenceId($application->getId());

        $this->entityManager->persist($notification);
    }
}

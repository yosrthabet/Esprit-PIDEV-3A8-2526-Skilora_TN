<?php

declare(strict_types=1);

namespace App\Recruitment\Entity;

use App\Enum\InterviewFormat;
use App\Enum\InterviewStatus;
use App\Recruitment\Repository\JobInterviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobInterviewRepository::class)]
#[ORM\Table(name: 'job_interviews')]
class JobInterview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Application $application;

    #[ORM\Column(name: 'scheduled_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\Column(name: 'duration_minutes')]
    private int $durationMinutes = 30;

    #[ORM\Column(length: 20, enumType: InterviewFormat::class)]
    private InterviewFormat $format = InterviewFormat::ONLINE;

    #[ORM\Column(name: 'meeting_provider', length: 40)]
    private string $meetingProvider = 'jitsi';

    #[ORM\Column(name: 'meeting_url', length: 700, nullable: true)]
    private ?string $meetingUrl = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20, enumType: InterviewStatus::class)]
    private InterviewStatus $status = InterviewStatus::SCHEDULED;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->scheduledAt = new \DateTimeImmutable('+1 day');
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getApplication(): Application { return $this->application; }
    public function setApplication(Application $application): static { $this->application = $application; return $this; }
    public function getScheduledAt(): \DateTimeImmutable { return $this->scheduledAt; }
    public function scheduleAt(\DateTimeImmutable $scheduledAt): static { $this->scheduledAt = $scheduledAt; return $this; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function setDurationMinutes(int $durationMinutes): static { $this->durationMinutes = max(15, $durationMinutes); return $this; }
    public function getFormat(): InterviewFormat { return $this->format; }
    public function setFormat(InterviewFormat $format): static { $this->format = $format; return $this; }
    public function getMeetingProvider(): string { return $this->meetingProvider; }
    public function setMeetingProvider(string $meetingProvider): static { $this->meetingProvider = $meetingProvider; return $this; }
    public function getMeetingUrl(): ?string { return $this->meetingUrl; }
    public function setMeetingUrl(?string $meetingUrl): static { $this->meetingUrl = $meetingUrl; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getStatus(): InterviewStatus { return $this->status; }
    public function setStatus(InterviewStatus $status): static { $this->status = $status; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

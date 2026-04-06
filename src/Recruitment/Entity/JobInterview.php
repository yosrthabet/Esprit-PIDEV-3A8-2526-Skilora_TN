<?php

declare(strict_types=1);

namespace App\Recruitment\Entity;

use App\Recruitment\InterviewFormat;
use App\Recruitment\InterviewLifecycle;
use App\Recruitment\Repository\JobInterviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobInterviewRepository::class)]
#[ORM\Table(name: 'interviews')]
class JobInterview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'interview')]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Application $application = null;

    /** Date et heure d'entretien. */
    #[ORM\Column(name: 'scheduled_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(name: 'duration_minutes', nullable: true)]
    private ?int $durationMinutes = null;

    /** ONLINE / ONSITE */
    #[ORM\Column(name: 'type', length: 20, options: ['default' => 'ONLINE'])]
    private string $format = InterviewFormat::ONLINE;

    /** Lieu de l'entretien (ex. ville, salle, lien visio si besoin). */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'video_link', length: 500, nullable: true)]
    private ?string $videoLink = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /** SCHEDULED / COMPLETED */
    #[ORM\Column(name: 'status', length: 20, options: ['default' => 'SCHEDULED'])]
    private string $lifecycleStatus = InterviewLifecycle::SCHEDULED;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedback = null;

    #[ORM\Column(nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $timezone = null;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): static
    {
        $this->application = $application;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(?int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFormatLabelFr(): string
    {
        return InterviewFormat::labelFr($this->format);
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location !== null ? trim($location) : null;
        if ($this->location === '') {
            $this->location = null;
        }

        return $this;
    }

    public function getVideoLink(): ?string
    {
        return $this->videoLink;
    }

    public function setVideoLink(?string $videoLink): static
    {
        $this->videoLink = $videoLink;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getLifecycleStatus(): string
    {
        return $this->lifecycleStatus;
    }

    public function setLifecycleStatus(string $lifecycleStatus): static
    {
        $this->lifecycleStatus = $lifecycleStatus;

        return $this;
    }

    public function getLifecycleLabelFr(): string
    {
        return InterviewLifecycle::labelFr($this->lifecycleStatus);
    }

    public function isCompleted(): bool
    {
        return $this->lifecycleStatus === InterviewLifecycle::COMPLETED;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): static
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}

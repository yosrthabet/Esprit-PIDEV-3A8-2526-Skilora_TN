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

    /** Date et heure d’entretien (fuseau serveur / PHP). Colonne SQL {@code interview_date} (schémas existants) ; {@code scheduled_at} est géré en copie par migration si besoin. */
    #[ORM\Column(name: 'interview_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    /** ONLINE / ONSITE — Doctrine utilise {@code interview_format} (migration + commande de secours si la base n’a ni format ni interview_format). */
    #[ORM\Column(name: 'interview_format', length: 20, options: ['default' => 'ONLINE'])]
    private string $format = InterviewFormat::ONLINE;

    /** Lieu de l’entretien (ex. ville, salle, lien visio si besoin). */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $location = null;

    /** SCHEDULED / COMPLETED — colonne SQL {@code interview_status} (souvent absente : ancien nom {@code lifecycle_status}). */
    #[ORM\Column(name: 'interview_status', length: 20, options: ['default' => 'SCHEDULED'])]
    private string $lifecycleStatus = InterviewLifecycle::SCHEDULED;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touchUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

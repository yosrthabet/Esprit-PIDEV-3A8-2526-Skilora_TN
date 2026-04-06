<?php

namespace App\Recruitment\Entity;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Repository\ApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'applications')]
#[ORM\UniqueConstraint(name: 'uniq_application_candidate', columns: ['job_offer_id', 'candidate_id'])]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JobOffer::class)]
    #[ORM\JoinColumn(name: 'job_offer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?JobOffer $jobOffer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $candidate = null;

    /** FK SQL vers `profiles.id` (NOT NULL en base). */
    #[ORM\Column(name: 'candidate_profile_id', nullable: false)]
    private ?int $candidateProfileId = null;

    /** Chemin relatif sous le répertoire d'upload (ex. 2026/04/abc.pdf) */
    #[ORM\Column(name: 'cv_path', length: 500)]
    private string $cvPath = '';

    #[ORM\Column(name: 'cover_letter', type: Types::TEXT, nullable: true)]
    private ?string $coverLetter = null;

    /** Colonne SQL héritée (souvent utilisée par l'ancienne app). */
    #[ORM\Column(name: 'custom_cv_url', type: Types::TEXT, nullable: true)]
    private ?string $customCvUrl = null;

    #[ORM\Column(name: 'match_percentage', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $matchPercentage = null;

    #[ORM\Column(name: 'candidate_score', nullable: true)]
    private ?int $candidateScore = null;

    /** Aligné sur la colonne SQL {@code status} (défaut MySQL {@code PENDING}). */
    #[ORM\Column(length: 30, nullable: true, options: ['default' => 'PENDING'])]
    private ?string $status = 'PENDING';

    /** Date héritée (`applied_date`) — peut différer de {@see $appliedAt}. */
    #[ORM\Column(name: 'applied_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $appliedDate = null;

    #[ORM\Column(name: 'applied_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $appliedAt;

    #[ORM\OneToOne(mappedBy: 'application', targetEntity: JobInterview::class, cascade: ['persist', 'remove'])]
    private ?JobInterview $interview = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->appliedAt = $now;
        $this->appliedDate = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobOffer(): ?JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(?JobOffer $jobOffer): static
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }

    public function getCandidate(): ?User
    {
        return $this->candidate;
    }

    public function setCandidate(?User $candidate): static
    {
        $this->candidate = $candidate;

        return $this;
    }

    public function getCandidateProfileId(): ?int
    {
        return $this->candidateProfileId;
    }

    public function setCandidateProfileId(?int $candidateProfileId): static
    {
        $this->candidateProfileId = $candidateProfileId;

        return $this;
    }

    public function getCvPath(): string
    {
        return $this->cvPath;
    }

    public function setCvPath(string $cvPath): static
    {
        $this->cvPath = $cvPath;

        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): static
    {
        $this->coverLetter = $coverLetter;

        return $this;
    }

    public function getCustomCvUrl(): ?string
    {
        return $this->customCvUrl;
    }

    public function setCustomCvUrl(?string $customCvUrl): static
    {
        $this->customCvUrl = $customCvUrl;

        return $this;
    }

    public function getMatchPercentage(): ?string
    {
        return $this->matchPercentage;
    }

    public function setMatchPercentage(?string $matchPercentage): static
    {
        $this->matchPercentage = $matchPercentage;

        return $this;
    }

    public function getCandidateScore(): ?int
    {
        return $this->candidateScore;
    }

    public function setCandidateScore(?int $candidateScore): static
    {
        $this->candidateScore = $candidateScore;

        return $this;
    }

    public function getAppliedDate(): ?\DateTimeImmutable
    {
        return $this->appliedDate;
    }

    public function setAppliedDate(?\DateTimeImmutable $appliedDate): static
    {
        $this->appliedDate = $appliedDate;

        return $this;
    }

    /** Valeur exacte de la colonne SQL `applications.status` (peut être NULL). */
    public function getStatusRaw(): ?string
    {
        return $this->status;
    }

    /** Valeur métier (défaut PENDING si NULL en base). */
    public function getStatus(): string
    {
        return $this->status ?? 'PENDING';
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** Libellé français pour l'UI (basé sur la valeur SQL réelle). */
    public function getStatusLabelFr(): string
    {
        $raw = $this->status;
        if ($raw === null || $raw === '') {
            return '—';
        }

        return ApplicationStatus::labelFr($raw);
    }

    public function getAppliedAt(): \DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(\DateTimeImmutable $appliedAt): static
    {
        $this->appliedAt = $appliedAt;

        return $this;
    }

    public function getInterview(): ?JobInterview
    {
        return $this->interview;
    }

    public function setInterview(?JobInterview $interview): static
    {
        if ($interview !== null && $interview->getApplication() !== $this) {
            $interview->setApplication($this);
        }
        $this->interview = $interview;

        return $this;
    }

    public function isAccepted(): bool
    {
        return ($this->status ?? '') === ApplicationStatus::ACCEPTED;
    }
}

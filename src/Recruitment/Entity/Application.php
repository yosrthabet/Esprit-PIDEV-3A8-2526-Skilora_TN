<?php

declare(strict_types=1);

namespace App\Recruitment\Entity;

use App\Entity\User;
use App\Enum\ApplicationStatus;
use App\Recruitment\Repository\ApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'applications')]
#[ORM\UniqueConstraint(name: 'uniq_application_candidate_job', columns: ['job_offer_id', 'candidate_id'])]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JobOffer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private JobOffer $jobOffer;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $candidate;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $coverLetter = null;

    #[ORM\Column(name: 'cv_path', length: 500, nullable: true)]
    private ?string $cvPath = null;

    #[ORM\Column(name: 'match_score')]
    private int $matchScore = 0;

    /** @var list<string> */
    #[ORM\Column(name: 'match_reasons', type: Types::JSON)]
    private array $matchReasons = [];

    #[ORM\Column(length: 20, enumType: ApplicationStatus::class)]
    private ApplicationStatus $status = ApplicationStatus::APPLIED;

    #[ORM\Column(name: 'applied_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $appliedAt;

    public function __construct()
    {
        $this->appliedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getJobOffer(): JobOffer { return $this->jobOffer; }
    public function setJobOffer(JobOffer $jobOffer): static { $this->jobOffer = $jobOffer; return $this; }
    public function getCandidate(): User { return $this->candidate; }
    public function setCandidate(User $candidate): static { $this->candidate = $candidate; return $this; }
    public function getCoverLetter(): ?string { return $this->coverLetter; }
    public function setCoverLetter(?string $coverLetter): static { $this->coverLetter = $coverLetter; return $this; }
    public function getCvPath(): ?string { return $this->cvPath; }
    public function setCvPath(?string $cvPath): static { $this->cvPath = $cvPath; return $this; }
    public function getMatchScore(): int { return $this->matchScore; }
    public function setMatchScore(int $matchScore): static { $this->matchScore = max(0, min(100, $matchScore)); return $this; }
    /** @return list<string> */
    public function getMatchReasons(): array { return array_values(array_filter($this->matchReasons, 'is_string')); }
    /** @param list<string> $matchReasons */
    public function setMatchReasons(array $matchReasons): static { $this->matchReasons = $matchReasons; return $this; }
    public function getStatus(): ApplicationStatus { return $this->status; }
    public function setStatus(ApplicationStatus $status): static { $this->status = $status; return $this; }
    public function getAppliedAt(): \DateTimeImmutable { return $this->appliedAt; }
}

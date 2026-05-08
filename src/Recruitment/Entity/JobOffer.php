<?php

declare(strict_types=1);

namespace App\Recruitment\Entity;

use App\Enum\Currency;
use App\Enum\ExperienceLevel;
use App\Enum\FeedSource;
use App\Enum\JobOfferStatus;
use App\Enum\WorkType;
use App\Recruitment\Repository\JobOfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
#[ORM\Table(name: 'job_offers')]
#[ORM\Index(columns: ['status', 'posted_at'], name: 'idx_job_status_posted')]
#[ORM\Index(columns: ['feed_source', 'feed_source_id'], name: 'idx_job_feed_source')]
class JobOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requirements = null;

    #[ORM\Column(name: 'skills_required', type: Types::TEXT, nullable: true)]
    private ?string $skillsRequired = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $benefits = null;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'work_type', length: 20, nullable: true, enumType: WorkType::class)]
    private ?WorkType $workType = null;

    #[ORM\Column(name: 'experience_level', length: 20, nullable: true, enumType: ExperienceLevel::class)]
    private ?ExperienceLevel $experienceLevel = null;

    #[ORM\Column(name: 'min_salary', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $minSalary = null;

    #[ORM\Column(name: 'max_salary', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $maxSalary = null;

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(length: 20, enumType: JobOfferStatus::class)]
    private JobOfferStatus $status = JobOfferStatus::OPEN;

    #[ORM\Column(name: 'company_name', length: 180, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(name: 'feed_source', length: 32, nullable: true, enumType: FeedSource::class)]
    private ?FeedSource $feedSource = null;

    #[ORM\Column(name: 'feed_source_id', length: 255, nullable: true)]
    private ?string $feedSourceId = null;

    #[ORM\Column(name: 'feed_url', length: 700, nullable: true)]
    private ?string $feedUrl = null;

    #[ORM\Column(name: 'source_quality')]
    private int $sourceQuality = 50;

    #[ORM\Column(name: 'views_count')]
    private int $viewsCount = 0;

    #[ORM\Column(name: 'applications_count')]
    private int $applicationsCount = 0;

    #[ORM\Column(name: 'is_featured')]
    private bool $featured = false;

    #[ORM\Column(name: 'posted_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $postedAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(name: 'closed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    public function __construct()
    {
        $this->postedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): static { $this->company = $company; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getRequirements(): ?string { return $this->requirements; }
    public function setRequirements(?string $requirements): static { $this->requirements = $requirements; return $this; }
    public function getSkillsRequired(): ?string { return $this->skillsRequired; }
    public function setSkillsRequired(?string $skillsRequired): static { $this->skillsRequired = $skillsRequired; return $this; }
    public function getBenefits(): ?string { return $this->benefits; }
    public function setBenefits(?string $benefits): static { $this->benefits = $benefits; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }
    public function getWorkType(): ?WorkType { return $this->workType; }
    public function setWorkType(?WorkType $workType): static { $this->workType = $workType; return $this; }
    public function getExperienceLevel(): ?ExperienceLevel { return $this->experienceLevel; }
    public function setExperienceLevel(?ExperienceLevel $experienceLevel): static { $this->experienceLevel = $experienceLevel; return $this; }
    public function getMinSalary(): ?string { return $this->minSalary; }
    public function setMinSalary(?string $minSalary): static { $this->minSalary = $minSalary; return $this; }
    public function getMaxSalary(): ?string { return $this->maxSalary; }
    public function setMaxSalary(?string $maxSalary): static { $this->maxSalary = $maxSalary; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getStatus(): JobOfferStatus { return $this->status; }
    public function setStatus(JobOfferStatus $status): static { $this->status = $status; return $this; }
    public function getCompanyName(): ?string { return $this->companyName; }
    public function setCompanyName(?string $companyName): static { $this->companyName = $companyName; return $this; }
    public function getFeedSource(): ?FeedSource { return $this->feedSource; }
    public function setFeedSource(?FeedSource $feedSource): static { $this->feedSource = $feedSource; return $this; }
    public function getFeedSourceId(): ?string { return $this->feedSourceId; }
    public function setFeedSourceId(?string $feedSourceId): static { $this->feedSourceId = $feedSourceId; return $this; }
    public function getFeedUrl(): ?string { return $this->feedUrl; }
    public function setFeedUrl(?string $feedUrl): static { $this->feedUrl = $feedUrl; return $this; }
    public function getSourceQuality(): int { return $this->sourceQuality; }
    public function setSourceQuality(int $sourceQuality): static { $this->sourceQuality = max(0, min(100, $sourceQuality)); return $this; }
    public function getViewsCount(): int { return $this->viewsCount; }
    public function incrementViews(): static { $this->viewsCount++; return $this; }
    public function getApplicationsCount(): int { return $this->applicationsCount; }
    public function incrementApplications(): static { $this->applicationsCount++; return $this; }
    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $featured): static { $this->featured = $featured; return $this; }
    public function getPostedAt(): \DateTimeImmutable { return $this->postedAt; }
    public function recordPostedAt(\DateTimeImmutable $postedAt): static { $this->postedAt = $postedAt; return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function scheduleExpiry(?\DateTimeImmutable $expiresAt): static { $this->expiresAt = $expiresAt; return $this; }
    public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
    public function close(?\DateTimeImmutable $at = null): static { $this->status = JobOfferStatus::CLOSED; $this->closedAt = $at ?? new \DateTimeImmutable(); return $this; }
    public function reopen(): static { $this->status = JobOfferStatus::OPEN; $this->closedAt = null; return $this; }
    public function getCompanyLabel(): string { return $this->company?->getName() ?? $this->companyName ?? 'External listing'; }
    public function isExternal(): bool { return $this->feedSource !== null && $this->feedSource !== FeedSource::PLATFORM; }

    /** @return array{score: int, label: string, missing: list<string>} */
    public function getReadiness(): array
    {
        $score = 0;
        $missing = [];

        if (mb_strlen(trim($this->title)) >= 8) { $score += 15; } else { $missing[] = 'Clear role title'; }
        if (mb_strlen(trim($this->description ?? '')) >= 80) { $score += 25; } else { $missing[] = 'Detailed description'; }
        if (mb_strlen(trim($this->requirements ?? '')) >= 30) { $score += 15; } else { $missing[] = 'Requirements'; }
        if (mb_strlen(trim($this->skillsRequired ?? '')) >= 3) { $score += 15; } else { $missing[] = 'Skills'; }
        if ($this->minSalary !== null || $this->maxSalary !== null) { $score += 15; } else { $missing[] = 'Salary range'; }
        if ($this->location !== null || $this->workType !== null) { $score += 10; } else { $missing[] = 'Location or work type'; }
        if ($this->expiresAt !== null) { $score += 5; } else { $missing[] = 'Application deadline'; }

        $label = match (true) {
            $score >= 91 => 'Excellent',
            $score >= 71 => 'Strong',
            $score >= 41 => 'Good start',
            default => 'Weak posting',
        };

        return ['score' => $score, 'label' => $label, 'missing' => $missing];
    }
}

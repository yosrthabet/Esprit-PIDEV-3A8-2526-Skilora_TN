<?php

namespace App\Recruitment\Entity;

use App\Entity\User;
use App\Recruitment\Repository\JobOfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
#[ORM\Table(name: 'job_offers')]
class JobOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(length: 100)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requirements = null;

    #[ORM\Column(name: 'min_salary', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $minSalary = null;

    #[ORM\Column(name: 'max_salary', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $maxSalary = null;

    #[ORM\Column(length: 10, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'work_type', length: 50, nullable: true)]
    private ?string $workType = null;

    #[ORM\Column(name: 'posted_date', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $postedDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\Column(length: 20, options: ['default' => 'OPEN'])]
    private string $status = 'OPEN';

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'experience_level', length: 30, nullable: true)]
    private ?string $experienceLevel = null;

    #[ORM\Column(name: 'skills_required', type: Types::TEXT, nullable: true)]
    private ?string $skillsRequired = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $benefits = null;

    #[ORM\Column(name: 'company_name', length: 150, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(name: 'is_featured', options: ['default' => 0])]
    private bool $isFeatured = false;

    #[ORM\Column(name: 'views_count', options: ['default' => 0])]
    private int $viewsCount = 0;

    #[ORM\Column(name: 'applications_count', options: ['default' => 0])]
    private int $applicationsCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    public function setRequirements(?string $requirements): static
    {
        $this->requirements = $requirements;

        return $this;
    }

    public function getMinSalary(): ?string
    {
        return $this->minSalary;
    }

    public function setMinSalary(?string $minSalary): static
    {
        $this->minSalary = $minSalary;

        return $this;
    }

    public function getMaxSalary(): ?string
    {
        return $this->maxSalary;
    }

    public function setMaxSalary(?string $maxSalary): static
    {
        $this->maxSalary = $maxSalary;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getWorkType(): ?string
    {
        return $this->workType;
    }

    public function setWorkType(?string $workType): static
    {
        $this->workType = $workType;

        return $this;
    }

    public function getPostedDate(): ?\DateTimeInterface
    {
        return $this->postedDate;
    }

    public function setPostedDate(?\DateTimeInterface $postedDate): static
    {
        $this->postedDate = $postedDate;

        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getExperienceLevel(): ?string
    {
        return $this->experienceLevel;
    }

    public function setExperienceLevel(?string $experienceLevel): static
    {
        $this->experienceLevel = $experienceLevel;

        return $this;
    }

    public function getSkillsRequired(): ?string
    {
        return $this->skillsRequired;
    }

    public function setSkillsRequired(?string $skillsRequired): static
    {
        $this->skillsRequired = $skillsRequired;

        return $this;
    }

    public function getBenefits(): ?string
    {
        return $this->benefits;
    }

    public function setBenefits(?string $benefits): static
    {
        $this->benefits = $benefits;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function setViewsCount(int $viewsCount): static
    {
        $this->viewsCount = $viewsCount;

        return $this;
    }

    public function getApplicationsCount(): int
    {
        return $this->applicationsCount;
    }

    public function setApplicationsCount(int $applicationsCount): static
    {
        $this->applicationsCount = $applicationsCount;

        return $this;
    }

    public function isOwnedBy(User $user): bool
    {
        $owner = $this->company?->getOwner();

        return $owner && $owner->getId() === $user->getId();
    }
}

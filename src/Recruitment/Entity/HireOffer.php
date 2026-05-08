<?php

declare(strict_types=1);

namespace App\Recruitment\Entity;

use App\Enum\Currency;
use App\Enum\HireOfferStatus;
use App\Recruitment\Repository\HireOfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HireOfferRepository::class)]
#[ORM\Table(name: 'hire_offers')]
class HireOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Application $application;

    #[ORM\Column(name: 'salary_offered', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $salaryOffered = null;

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(name: 'contract_type', length: 80, nullable: true)]
    private ?string $contractType = null;

    #[ORM\Column(name: 'start_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $benefits = null;

    #[ORM\Column(length: 20, enumType: HireOfferStatus::class)]
    private HireOfferStatus $status = HireOfferStatus::PENDING;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'responded_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getApplication(): Application { return $this->application; }
    public function setApplication(Application $application): static { $this->application = $application; return $this; }
    public function getSalaryOffered(): ?string { return $this->salaryOffered; }
    public function setSalaryOffered(?string $salaryOffered): static { $this->salaryOffered = $salaryOffered; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getContractType(): ?string { return $this->contractType; }
    public function setContractType(?string $contractType): static { $this->contractType = $contractType; return $this; }
    public function getStartDate(): ?\DateTimeImmutable { return $this->startDate; }
    public function setStartDate(?\DateTimeImmutable $startDate): static { $this->startDate = $startDate; return $this; }
    public function getBenefits(): ?string { return $this->benefits; }
    public function setBenefits(?string $benefits): static { $this->benefits = $benefits; return $this; }
    public function getStatus(): HireOfferStatus { return $this->status; }
    public function accept(): static { $this->status = HireOfferStatus::ACCEPTED; $this->respondedAt = new \DateTimeImmutable(); return $this; }
    public function reject(): static { $this->status = HireOfferStatus::REJECTED; $this->respondedAt = new \DateTimeImmutable(); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getRespondedAt(): ?\DateTimeImmutable { return $this->respondedAt; }
}

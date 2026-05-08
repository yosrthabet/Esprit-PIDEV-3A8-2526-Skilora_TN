<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Enum\ContractStatus;
use App\Enum\Currency;
use App\Finance\Repository\ContractRepository;
use App\Recruitment\Entity\HireOffer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: 'finance_contracts')]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: HireOffer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private HireOffer $hireOffer;

    #[ORM\Column(length: 30, enumType: ContractStatus::class)]
    private ContractStatus $status = ContractStatus::PENDING_FUNDING;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(name: 'escrow_funded_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $escrowFundedAt = null;

    #[ORM\Column(name: 'submitted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(name: 'approved_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(name: 'released_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\Column(name: 'disputed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $disputedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ContractDelivery> */
    #[ORM\OneToMany(mappedBy: 'contract', targetEntity: ContractDelivery::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $deliveries;

    /** @var Collection<int, ContractDispute> */
    #[ORM\OneToMany(mappedBy: 'contract', targetEntity: ContractDispute::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $disputes;

    /** @var Collection<int, EscrowTransaction> */
    #[ORM\OneToMany(mappedBy: 'contract', targetEntity: EscrowTransaction::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $transactions;

    /** @var Collection<int, Invoice> */
    #[ORM\OneToMany(mappedBy: 'contract', targetEntity: Invoice::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $invoices;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->deliveries = new ArrayCollection();
        $this->disputes = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->invoices = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getHireOffer(): HireOffer { return $this->hireOffer; }
    public function setHireOffer(HireOffer $hireOffer): static { $this->hireOffer = $hireOffer; return $this; }
    public function getStatus(): ContractStatus { return $this->status; }
    public function setStatus(ContractStatus $status): static { $this->status = $status; $this->touch(); return $this; }
    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(?string $amount): static { $this->amount = $amount; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getEscrowFundedAt(): ?\DateTimeImmutable { return $this->escrowFundedAt; }
    public function fundEscrow(?\DateTimeImmutable $at = null): static { $this->assertStatus(ContractStatus::PENDING_FUNDING); $this->escrowFundedAt = $at ?? new \DateTimeImmutable(); $this->status = ContractStatus::ACTIVE; $this->touch(); return $this; }
    public function submitDelivery(?\DateTimeImmutable $at = null): static { $this->assertStatus(ContractStatus::ACTIVE); $this->submittedAt = $at ?? new \DateTimeImmutable(); $this->status = ContractStatus::SUBMITTED; $this->touch(); return $this; }
    public function approveDelivery(?\DateTimeImmutable $at = null): static { $this->assertStatus(ContractStatus::SUBMITTED); $this->approvedAt = $at ?? new \DateTimeImmutable(); $this->status = ContractStatus::APPROVED; $this->touch(); return $this; }
    public function releaseEscrow(?\DateTimeImmutable $at = null): static { $this->assertStatus(ContractStatus::APPROVED); $this->releasedAt = $at ?? new \DateTimeImmutable(); $this->status = ContractStatus::CLOSED; $this->touch(); return $this; }
    public function openDispute(?\DateTimeImmutable $at = null): static { if (!in_array($this->status, [ContractStatus::ACTIVE, ContractStatus::SUBMITTED, ContractStatus::APPROVED], true)) { throw new \RuntimeException('Contract cannot be disputed in its current state.'); } $this->disputedAt = $at ?? new \DateTimeImmutable(); $this->status = ContractStatus::DISPUTED; $this->touch(); return $this; }
    public function closeFromDispute(?\DateTimeImmutable $at = null): static { $this->assertStatus(ContractStatus::DISPUTED); $this->releasedAt = $at ?? new \DateTimeImmutable(); $this->status = ContractStatus::CLOSED; $this->touch(); return $this; }
    public function getSubmittedAt(): ?\DateTimeImmutable { return $this->submittedAt; }
    public function getApprovedAt(): ?\DateTimeImmutable { return $this->approvedAt; }
    public function getReleasedAt(): ?\DateTimeImmutable { return $this->releasedAt; }
    public function getDisputedAt(): ?\DateTimeImmutable { return $this->disputedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getEmployer(): ?User { return $this->hireOffer->getApplication()->getJobOffer()->getCompany()?->getOwner(); }
    public function getFreelancer(): User { return $this->hireOffer->getApplication()->getCandidate(); }
    /** @return Collection<int, ContractDelivery> */
    public function getDeliveries(): Collection { return $this->deliveries; }
    /** @return Collection<int, ContractDispute> */
    public function getDisputes(): Collection { return $this->disputes; }
    /** @return Collection<int, EscrowTransaction> */
    public function getTransactions(): Collection { return $this->transactions; }
    /** @return Collection<int, Invoice> */
    public function getInvoices(): Collection { return $this->invoices; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function assertStatus(ContractStatus $expected): void
    {
        if ($this->status !== $expected) {
            throw new \RuntimeException(sprintf('Expected contract status %s, got %s.', $expected->value, $this->status->value));
        }
    }
}

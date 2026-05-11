<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Enum\Currency;
use App\Finance\Repository\ContractMilestoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractMilestoneRepository::class)]
#[ORM\Table(name: 'finance_contract_milestones')]
class ContractMilestone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Contract::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contract $contract;
    #[ORM\Column(length: 180)]
    private string $title = '';
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount = '0.00';
    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;
    #[ORM\Column(length: 20)]
    private string $status = 'pending';
    #[ORM\Column(name: 'due_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueAt = null;
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;
    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = trim($title); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description !== null ? trim($description) : null; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getStatus(): string { return $this->status; }
    public function markPaid(): void { $this->status = 'paid'; }
    public function cancel(): void { $this->status = 'cancelled'; }
    public function getDueAt(): ?\DateTimeImmutable { return $this->dueAt; }
    public function setDueAt(?\DateTimeImmutable $dueAt): static { $this->dueAt = $dueAt; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

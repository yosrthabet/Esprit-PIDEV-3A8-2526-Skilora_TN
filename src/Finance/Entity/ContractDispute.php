<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Enum\DisputeStatus;
use App\Finance\Repository\ContractDisputeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractDisputeRepository::class)]
#[ORM\Table(name: 'finance_contract_disputes')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_finance_dispute_status_created')]
class ContractDispute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contract::class, inversedBy: 'disputes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contract $contract;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $openedBy;

    #[ORM\Column(length: 180)]
    private string $reason = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 20, enumType: DisputeStatus::class)]
    private DisputeStatus $status = DisputeStatus::OPEN;

    #[ORM\Column(name: 'admin_resolution', type: Types::TEXT, nullable: true)]
    private ?string $adminResolution = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'resolved_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }
    public function getOpenedBy(): User { return $this->openedBy; }
    public function setOpenedBy(User $openedBy): static { $this->openedBy = $openedBy; return $this; }
    public function getReason(): string { return $this->reason; }
    public function setReason(string $reason): static { $this->reason = trim($reason); return $this; }
    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): static { $this->details = $details !== null ? trim($details) : null; return $this; }
    public function getStatus(): DisputeStatus { return $this->status; }
    public function getAdminResolution(): ?string { return $this->adminResolution; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getResolvedAt(): ?\DateTimeImmutable { return $this->resolvedAt; }
    public function resolve(string $resolution): static { $this->status = DisputeStatus::RESOLVED; $this->adminResolution = trim($resolution); $this->resolvedAt = new \DateTimeImmutable(); return $this; }
}

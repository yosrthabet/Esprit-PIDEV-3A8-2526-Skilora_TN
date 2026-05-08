<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Enum\Currency;
use App\Enum\EscrowTransactionType;
use App\Finance\Repository\EscrowTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EscrowTransactionRepository::class)]
#[ORM\Table(name: 'finance_escrow_transactions')]
class EscrowTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contract::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contract $contract;

    #[ORM\Column(length: 20, enumType: EscrowTransactionType::class)]
    private EscrowTransactionType $type = EscrowTransactionType::FUND;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(length: 20)]
    private string $status = 'succeeded';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }
    public function getType(): EscrowTransactionType { return $this->type; }
    public function setType(EscrowTransactionType $type): static { $this->type = $type; return $this; }
    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(?string $amount): static { $this->amount = $amount; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

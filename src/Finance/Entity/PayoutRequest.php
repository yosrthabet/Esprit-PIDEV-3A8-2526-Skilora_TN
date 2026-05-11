<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Enum\Currency;
use App\Enum\PayoutStatus;
use App\Finance\Repository\PayoutRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayoutRequestRepository::class)]
#[ORM\Table(name: 'finance_payout_requests')]
#[ORM\Index(columns: ['user_id', 'status', 'created_at'], name: 'idx_payout_user_status_created')]
class PayoutRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: BankAccount::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private BankAccount $bankAccount;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(length: 20, enumType: PayoutStatus::class)]
    private PayoutStatus $status = PayoutStatus::PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(name: 'admin_note', type: Types::TEXT, nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column(name: 'processed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getBankAccount(): BankAccount { return $this->bankAccount; }
    public function setBankAccount(BankAccount $bankAccount): static { $this->bankAccount = $bankAccount; return $this; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }

    public function getStatus(): PayoutStatus { return $this->status; }
    public function setStatus(PayoutStatus $status): static { $this->status = $status; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $this->note = $note; return $this; }

    public function getAdminNote(): ?string { return $this->adminNote; }
    public function setAdminNote(?string $adminNote): static { $this->adminNote = $adminNote; return $this; }

    public function getProcessedAt(): ?\DateTimeImmutable { return $this->processedAt; }
    public function markProcessed(): static { $this->processedAt = new \DateTimeImmutable(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isPending(): bool { return $this->status === PayoutStatus::PENDING; }
}

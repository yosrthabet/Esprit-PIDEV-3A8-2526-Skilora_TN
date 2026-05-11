<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Enum\Currency;
use App\Finance\Repository\PaymentTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentTransactionRepository::class)]
#[ORM\Table(name: 'finance_payment_transactions')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_finance_payment_user_created')]
class PaymentTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 40)]
    private string $type = 'topup';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(length: 30)]
    private string $status = 'succeeded';

    #[ORM\Column(name: 'provider_reference', length: 120, nullable: true)]
    private ?string $providerReference = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getProviderReference(): ?string { return $this->providerReference; }
    public function setProviderReference(?string $providerReference): static { $this->providerReference = $providerReference; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

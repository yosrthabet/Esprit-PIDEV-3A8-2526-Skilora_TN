<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Enum\Currency;
use App\Finance\Repository\WalletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
#[ORM\Table(name: 'finance_wallets')]
#[ORM\UniqueConstraint(name: 'uniq_finance_wallet_user_currency', columns: ['user_id', 'currency'])]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $balance = '0.00';

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getBalance(): string { return $this->balance; }
    public function setBalance(string $balance): static { $this->balance = $balance; $this->touch(); return $this; }
    public function credit(string $amount): void { $this->balance = number_format((float) $this->balance + (float) $amount, 2, '.', ''); $this->touch(); }
    public function debit(string $amount): void { if ((float) $this->balance < (float) $amount) { throw new \RuntimeException('Insufficient wallet balance.'); } $this->balance = number_format((float) $this->balance - (float) $amount, 2, '.', ''); $this->touch(); }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}

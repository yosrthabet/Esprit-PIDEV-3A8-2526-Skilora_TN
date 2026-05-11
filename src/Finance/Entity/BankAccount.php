<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Finance\Repository\BankAccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
#[ORM\Table(name: 'finance_bank_accounts')]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'bank_name', length: 120)]
    private string $bankName = '';

    #[ORM\Column(name: 'account_holder', length: 160)]
    private string $accountHolder = '';

    #[ORM\Column(length: 64)]
    private string $iban = '';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $swift = null;

    #[ORM\Column(name: 'is_primary')]
    private bool $primary = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getBankName(): string { return $this->bankName; }
    public function setBankName(string $bankName): static { $this->bankName = trim($bankName); return $this; }
    public function getAccountHolder(): string { return $this->accountHolder; }
    public function setAccountHolder(string $accountHolder): static { $this->accountHolder = trim($accountHolder); return $this; }
    public function getIban(): string { return $this->iban; }
    public function setIban(string $iban): static { $this->iban = strtoupper(str_replace(' ', '', $iban)); return $this; }
    public function getSwift(): ?string { return $this->swift; }
    public function setSwift(?string $swift): static { $this->swift = $swift !== null ? strtoupper(trim($swift)) : null; return $this; }
    public function isPrimary(): bool { return $this->primary; }
    public function setPrimary(bool $primary): static { $this->primary = $primary; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getMaskedIban(): string { return strlen($this->iban) > 8 ? substr($this->iban, 0, 4) . '...' . substr($this->iban, -4) : $this->iban; }
}

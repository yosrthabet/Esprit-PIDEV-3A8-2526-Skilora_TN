<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Enum\Currency;
use App\Enum\InvoiceStatus;
use App\Finance\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'finance_invoices')]
#[ORM\UniqueConstraint(name: 'uniq_finance_invoice_number', columns: ['number'])]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contract::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contract $contract;

    #[ORM\Column(length: 40)]
    private string $number = '';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $issuer;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    #[ORM\Column(length: 20, enumType: InvoiceStatus::class)]
    private InvoiceStatus $status = InvoiceStatus::ISSUED;

    #[ORM\Column(name: 'issued_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $issuedAt;

    #[ORM\Column(name: 'paid_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->issuedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }
    public function getNumber(): string { return $this->number; }
    public function setNumber(string $number): static { $this->number = $number; return $this; }
    public function getIssuer(): User { return $this->issuer; }
    public function setIssuer(User $issuer): static { $this->issuer = $issuer; return $this; }
    public function getRecipient(): User { return $this->recipient; }
    public function setRecipient(User $recipient): static { $this->recipient = $recipient; return $this; }
    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(?string $amount): static { $this->amount = $amount; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getStatus(): InvoiceStatus { return $this->status; }
    public function markPaid(): static { $this->status = InvoiceStatus::PAID; $this->paidAt = new \DateTimeImmutable(); return $this; }
    public function getIssuedAt(): \DateTimeImmutable { return $this->issuedAt; }
    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

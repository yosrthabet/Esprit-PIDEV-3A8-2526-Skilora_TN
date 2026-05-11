<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Enum\Currency;
use App\Finance\Repository\PayslipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayslipRepository::class)]
#[ORM\Table(name: 'finance_payslips')]
class Payslip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $employee;
    #[ORM\Column(name: 'period_start', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $periodStart;
    #[ORM\Column(name: 'period_end', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $periodEnd;
    #[ORM\Column(name: 'gross_amount', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $grossAmount = '0.00';
    #[ORM\Column(name: 'net_amount', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $netAmount = '0.00';
    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;
    #[ORM\Column(length: 20)]
    private string $status = 'issued';
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;
    public function __construct() { $this->periodStart = new \DateTimeImmutable('first day of this month'); $this->periodEnd = new \DateTimeImmutable('last day of this month'); $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getEmployee(): User { return $this->employee; }
    public function setEmployee(User $employee): static { $this->employee = $employee; return $this; }
    public function getPeriodStart(): \DateTimeImmutable { return $this->periodStart; }
    public function setPeriodStart(\DateTimeImmutable $periodStart): static { $this->periodStart = $periodStart; return $this; }
    public function getPeriodEnd(): \DateTimeImmutable { return $this->periodEnd; }
    public function setPeriodEnd(\DateTimeImmutable $periodEnd): static { $this->periodEnd = $periodEnd; return $this; }
    public function getGrossAmount(): string { return $this->grossAmount; }
    public function setGrossAmount(string $grossAmount): static { $this->grossAmount = $grossAmount; return $this; }
    public function getNetAmount(): string { return $this->netAmount; }
    public function setNetAmount(string $netAmount): static { $this->netAmount = $netAmount; return $this; }
    public function getCurrency(): Currency { return $this->currency; }
    public function setCurrency(Currency $currency): static { $this->currency = $currency; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

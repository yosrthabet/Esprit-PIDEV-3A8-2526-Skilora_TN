<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Enum\Currency;
use App\Finance\Repository\ExchangeRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRateRepository::class)]
#[ORM\Table(name: 'finance_exchange_rates')]
class ExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(name: 'base_currency', length: 3, enumType: Currency::class)]
    private Currency $baseCurrency = Currency::TND;
    #[ORM\Column(name: 'quote_currency', length: 3, enumType: Currency::class)]
    private Currency $quoteCurrency = Currency::EUR;
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 6)]
    private string $rate = '1.000000';
    #[ORM\Column(name: 'effective_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $effectiveAt;
    public function __construct() { $this->effectiveAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getBaseCurrency(): Currency { return $this->baseCurrency; }
    public function setBaseCurrency(Currency $baseCurrency): static { $this->baseCurrency = $baseCurrency; return $this; }
    public function getQuoteCurrency(): Currency { return $this->quoteCurrency; }
    public function setQuoteCurrency(Currency $quoteCurrency): static { $this->quoteCurrency = $quoteCurrency; return $this; }
    public function getRate(): string { return $this->rate; }
    public function setRate(string $rate): static { $this->rate = $rate; return $this; }
    public function getEffectiveAt(): \DateTimeImmutable { return $this->effectiveAt; }
}

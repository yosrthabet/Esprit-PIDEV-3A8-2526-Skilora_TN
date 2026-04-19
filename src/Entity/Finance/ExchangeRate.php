<?php

namespace App\Entity\Finance;

use App\Repository\Finance\ExchangeRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRateRepository::class)]
#[ORM\Table(
    name: 'exchange_rates',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uq_rate', columns: ['from_currency', 'to_currency', 'rate_date']),
    ],
)]
#[ORM\Index(name: 'idx_exchange_rates_currencies', columns: ['from_currency', 'to_currency'])]
class ExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'from_currency', length: 10)]
    private string $fromCurrency = '';

    #[ORM\Column(name: 'to_currency', length: 10)]
    private string $toCurrency = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 6)]
    private string $rate = '0.000000';

    #[ORM\Column(name: 'rate_date', type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $rateDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $source = 'BCT';

    #[ORM\Column(name: 'last_updated', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastUpdated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromCurrency(): string
    {
        return $this->fromCurrency;
    }

    public function setFromCurrency(string $fromCurrency): static
    {
        $this->fromCurrency = $fromCurrency;

        return $this;
    }

    public function getToCurrency(): string
    {
        return $this->toCurrency;
    }

    public function setToCurrency(string $toCurrency): static
    {
        $this->toCurrency = $toCurrency;

        return $this;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function setRate(string $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getRateDate(): ?\DateTimeImmutable
    {
        return $this->rateDate;
    }

    public function setRateDate(\DateTimeImmutable $rateDate): static
    {
        $this->rateDate = $rateDate;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getLastUpdated(): ?\DateTimeImmutable
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?\DateTimeImmutable $lastUpdated): static
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }
}

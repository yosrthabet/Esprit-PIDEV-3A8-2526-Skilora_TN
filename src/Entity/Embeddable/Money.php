<?php

declare(strict_types=1);

namespace App\Entity\Embeddable;

use App\Enum\Currency;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Money
{
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 3, enumType: Currency::class)]
    private Currency $currency = Currency::TND;

    public function __construct(string $amount = '0.00', Currency $currency = Currency::TND)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAmountFloat(): float
    {
        return (float) $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function format(): string
    {
        return number_format($this->getAmountFloat(), 2, '.', ',') . ' ' . $this->currency->value;
    }

    public function isZero(): bool
    {
        /** @var numeric-string $a */
        $a = $this->amount;

        return bccomp($a, '0', 2) === 0;
    }

    public function isPositive(): bool
    {
        /** @var numeric-string $a */
        $a = $this->amount;

        return bccomp($a, '0', 2) > 0;
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies.');
        }
        /** @var numeric-string $a */
        $a = $this->amount;
        /** @var numeric-string $b */
        $b = $other->amount;

        return new self(bcadd($a, $b, 2), $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract different currencies.');
        }
        /** @var numeric-string $a */
        $a = $this->amount;
        /** @var numeric-string $b */
        $b = $other->amount;

        return new self(bcsub($a, $b, 2), $this->currency);
    }
}

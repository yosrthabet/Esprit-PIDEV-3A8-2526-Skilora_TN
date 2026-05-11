<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Embeddable\Money;
use App\Enum\Currency;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testDefaults(): void
    {
        $money = new Money();
        $this->assertSame('0.00', $money->getAmount());
        $this->assertSame(Currency::TND, $money->getCurrency());
        $this->assertTrue($money->isZero());
        $this->assertFalse($money->isPositive());
    }

    public function testAdd(): void
    {
        $a = new Money('10.50', Currency::TND);
        $b = new Money('5.25', Currency::TND);
        $sum = $a->add($b);

        $this->assertSame('15.75', $sum->getAmount());
        $this->assertSame(Currency::TND, $sum->getCurrency());
    }

    public function testSubtract(): void
    {
        $a = new Money('10.00', Currency::TND);
        $b = new Money('3.50', Currency::TND);
        $diff = $a->subtract($b);

        $this->assertSame('6.50', $diff->getAmount());
    }

    public function testCurrencyMismatchThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $a = new Money('10.00', Currency::TND);
        $b = new Money('5.00', Currency::EUR);
        $a->add($b);
    }

    public function testFormat(): void
    {
        $money = new Money('1234.50', Currency::TND);
        $this->assertSame('1,234.50 TND', $money->format());
    }

    public function testIsPositive(): void
    {
        $money = new Money('0.01', Currency::TND);
        $this->assertTrue($money->isPositive());
        $this->assertFalse($money->isZero());
    }
}

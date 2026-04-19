<?php

namespace App\Tests\Entity\Finance;

use App\Entity\Finance\ExchangeRate;
use PHPUnit\Framework\TestCase;

class ExchangeRateTest extends TestCase
{
    public function testNewIdIsNull(): void
    {
        $this->assertNull((new ExchangeRate())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $er = new ExchangeRate();
        $er->setFromCurrency('USD');
        $er->setToCurrency('TND');
        $er->setRate('3.1250');
        $d = new \DateTimeImmutable('2026-04-15');
        $er->setRateDate($d);
        $er->setSource('BCT');

        $this->assertSame('USD', $er->getFromCurrency());
        $this->assertSame('TND', $er->getToCurrency());
        $this->assertSame('3.1250', $er->getRate());
        $this->assertSame($d, $er->getRateDate());
        $this->assertSame('BCT', $er->getSource());
    }

    public function testLastUpdated(): void
    {
        $er = new ExchangeRate();
        $now = new \DateTimeImmutable();
        $er->setLastUpdated($now);
        $this->assertSame($now, $er->getLastUpdated());
    }

    public function testNullDefaults(): void
    {
        $er = new ExchangeRate();
        $this->assertNull($er->getRateDate());
        $this->assertSame('BCT', $er->getSource()); // default is 'BCT'
        $this->assertNull($er->getLastUpdated());
    }
}

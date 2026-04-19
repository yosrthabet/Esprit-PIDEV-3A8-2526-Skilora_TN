<?php

namespace App\Tests\Entity\Finance;

use App\Entity\Finance\Payslip;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PayslipTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('payuser');
        $u->setEmail('pay@test.com');
        $u->setFullName('Pay User');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    private function createPayslip(): Payslip
    {
        $p = new Payslip();
        $p->setUser($this->makeUser());
        $p->setMonth(4);
        $p->setYear(2026);
        $p->setBaseSalary(3000.0);
        $p->setOvertimeHours(10.0);
        $p->setOvertimeTotal(300.0);
        $p->setBonuses(200.0);
        $p->setOtherDeductions(50.0);
        $p->setCurrency('TND');
        $p->setStatus('DRAFT');

        return $p;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new Payslip())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $p = $this->createPayslip();

        $this->assertSame(4, $p->getMonth());
        $this->assertSame(2026, $p->getYear());
        $this->assertSame(3000.0, $p->getBaseSalary());
        $this->assertSame(10.0, $p->getOvertimeHours());
        $this->assertSame(300.0, $p->getOvertimeTotal());
        $this->assertSame(200.0, $p->getBonuses());
        $this->assertSame(50.0, $p->getOtherDeductions());
        $this->assertSame('TND', $p->getCurrency());
        $this->assertSame('DRAFT', $p->getStatus());
    }

    public function testEstimatedGross(): void
    {
        $p = $this->createPayslip();
        // base + overtime + bonuses
        $this->assertEqualsWithDelta(3500.0, $p->getEstimatedGross(), 0.01);
    }

    public function testComputedCnss(): void
    {
        $p = $this->createPayslip();
        // CNSS = 9.18% of gross
        $gross = $p->getEstimatedGross();
        $this->assertEqualsWithDelta($gross * 0.0918, $p->getComputedCnss(), 0.01);
    }

    public function testDeductionsJson(): void
    {
        $p = new Payslip();
        $p->setDeductionsJson('{"tax": 100}');
        $this->assertSame('{"tax": 100}', $p->getDeductionsJson());
    }

    public function testBonusesJson(): void
    {
        $p = new Payslip();
        $p->setBonusesJson('{"perf": 200}');
        $this->assertSame('{"perf": 200}', $p->getBonusesJson());
    }

    public function testNullDefaults(): void
    {
        $p = new Payslip();
        $this->assertNull($p->getMonth());
        $this->assertNull($p->getYear());
        $this->assertNull($p->getBaseSalary());
        $this->assertNull($p->getStatus());
        $this->assertNull($p->getDeductionsJson());
        $this->assertNull($p->getBonusesJson());
    }
}

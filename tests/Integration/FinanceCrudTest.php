<?php

namespace App\Tests\Integration;

use App\Entity\Finance\BankAccount;
use App\Entity\Finance\Bonus;
use App\Entity\Finance\Contract;
use App\Entity\Finance\ExchangeRate;
use App\Entity\Finance\Payslip;
use App\Recruitment\Entity\Company;

class FinanceCrudTest extends DatabaseTestCase
{
    private function getOrCreateCompany(): Company
    {
        $repo = $this->em->getRepository(Company::class);
        $existing = $repo->findOneBy([]);
        if ($existing) {
            return $existing;
        }
        $c = new Company();
        $c->setName('TestCorp');
        $c->setCountry('Tunisia');
        $this->em->persist($c);
        $this->em->flush();

        return $c;
    }

    // --- BankAccount ---

    public function testCreateBankAccount(): void
    {
        $user = $this->createTestUser();
        $ba = new BankAccount();
        $ba->setUser($user);
        $ba->setBankName('BIAT');
        $ba->setIban('TN5910006035183598983943');
        $ba->setSwift('BIATTNTT');
        $ba->setCurrency('TND');
        $this->em->persist($ba);
        $this->em->flush();

        $this->assertNotNull($ba->getId());
    }

    public function testReadBankAccount(): void
    {
        $user = $this->createTestUser();
        $ba = new BankAccount();
        $ba->setUser($user);
        $ba->setBankName('STB');
        $ba->setIban('TN5910006035183598983943');
        $ba->setSwift('STBKTNTT');
        $ba->setCurrency('TND');
        $this->em->persist($ba);
        $this->em->flush();
        $id = $ba->getId();
        $this->em->clear();

        $found = $this->em->find(BankAccount::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('STB', $found->getBankName());
    }

    public function testUpdateBankAccount(): void
    {
        $user = $this->createTestUser();
        $ba = new BankAccount();
        $ba->setUser($user);
        $ba->setBankName('BNA');
        $ba->setIban('TN5910006035183598983943');
        $ba->setSwift('BNAATNTX');
        $ba->setCurrency('TND');
        $this->em->persist($ba);
        $this->em->flush();

        $ba->setIsPrimary(true);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(BankAccount::class, $ba->getId());
        $this->assertTrue($found->isPrimary());
    }

    public function testDeleteBankAccount(): void
    {
        $user = $this->createTestUser();
        $ba = new BankAccount();
        $ba->setUser($user);
        $ba->setBankName('Del');
        $ba->setIban('TN5910006035183598983943');
        $ba->setSwift('DELXXXXX');
        $ba->setCurrency('TND');
        $this->em->persist($ba);
        $this->em->flush();
        $id = $ba->getId();

        $this->em->remove($ba);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(BankAccount::class, $id));
    }

    // --- Contract ---

    public function testCreateContract(): void
    {
        $user = $this->createTestUser();
        $company = $this->getOrCreateCompany();

        $ct = new Contract();
        $ct->setUser($user);
        $ct->setCompany($company);
        $ct->setType('CDI');
        $ct->setPosition('Developer');
        $ct->setSalary(3000.0);
        $ct->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $ct->setEndDate(new \DateTimeImmutable('2025-12-31'));
        $ct->setStatus('ACTIVE');
        $this->em->persist($ct);
        $this->em->flush();

        $this->assertNotNull($ct->getId());
    }

    public function testReadContract(): void
    {
        $user = $this->createTestUser();
        $company = $this->getOrCreateCompany();

        $ct = new Contract();
        $ct->setUser($user);
        $ct->setCompany($company);
        $ct->setType('CDD');
        $ct->setPosition('Analyst');
        $ct->setSalary(2500.0);
        $ct->setStartDate(new \DateTimeImmutable('2024-06-01'));
        $ct->setEndDate(new \DateTimeImmutable('2024-12-31'));
        $ct->setStatus('ACTIVE');
        $this->em->persist($ct);
        $this->em->flush();
        $id = $ct->getId();
        $this->em->clear();

        $found = $this->em->find(Contract::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Analyst', $found->getPosition());
    }

    public function testUpdateContract(): void
    {
        $user = $this->createTestUser();
        $company = $this->getOrCreateCompany();

        $ct = new Contract();
        $ct->setUser($user);
        $ct->setCompany($company);
        $ct->setType('CDI');
        $ct->setPosition('Dev');
        $ct->setSalary(3000.0);
        $ct->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $ct->setEndDate(new \DateTimeImmutable('2025-12-31'));
        $ct->setStatus('ACTIVE');
        $this->em->persist($ct);
        $this->em->flush();

        $ct->setStatus('TERMINATED');
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(Contract::class, $ct->getId());
        $this->assertSame('TERMINATED', $found->getStatus());
    }

    public function testDeleteContract(): void
    {
        $user = $this->createTestUser();
        $company = $this->getOrCreateCompany();

        $ct = new Contract();
        $ct->setUser($user);
        $ct->setCompany($company);
        $ct->setType('CDI');
        $ct->setPosition('Dev');
        $ct->setSalary(3000.0);
        $ct->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $ct->setEndDate(new \DateTimeImmutable('2025-12-31'));
        $ct->setStatus('ACTIVE');
        $this->em->persist($ct);
        $this->em->flush();
        $id = $ct->getId();

        $this->em->remove($ct);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(Contract::class, $id));
    }

    // --- Bonus ---

    public function testCreateBonus(): void
    {
        $user = $this->createTestUser();
        $b = new Bonus();
        $b->setUser($user);
        $b->setAmount(500.0);
        $b->setReason('Performance');
        $b->setDateAwarded(new \DateTimeImmutable());
        $this->em->persist($b);
        $this->em->flush();

        $this->assertNotNull($b->getId());
    }

    public function testReadBonus(): void
    {
        $user = $this->createTestUser();
        $b = new Bonus();
        $b->setUser($user);
        $b->setAmount(300.0);
        $b->setReason('Referral');
        $b->setDateAwarded(new \DateTimeImmutable());
        $this->em->persist($b);
        $this->em->flush();
        $id = $b->getId();
        $this->em->clear();

        $found = $this->em->find(Bonus::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Referral', $found->getReason());
    }

    // --- Payslip ---

    public function testCreatePayslip(): void
    {
        $user = $this->createTestUser();
        $p = new Payslip();
        $p->setUser($user);
        $p->setMonth(4);
        $p->setYear(2026);
        $p->setBaseSalary(3000.0);
        $p->setOvertimeHours(0.0);
        $p->setOvertimeTotal(0.0);
        $p->setBonuses(0.0);
        $p->setOtherDeductions(0.0);
        $p->setCurrency('TND');
        $p->setStatus('DRAFT');
        $this->em->persist($p);
        $this->em->flush();

        $this->assertNotNull($p->getId());
    }

    public function testReadPayslip(): void
    {
        $user = $this->createTestUser();
        $p = new Payslip();
        $p->setUser($user);
        $p->setMonth(3);
        $p->setYear(2026);
        $p->setBaseSalary(2800.0);
        $p->setOvertimeHours(5.0);
        $p->setOvertimeTotal(150.0);
        $p->setBonuses(100.0);
        $p->setOtherDeductions(25.0);
        $p->setCurrency('TND');
        $p->setStatus('PENDING');
        $this->em->persist($p);
        $this->em->flush();
        $id = $p->getId();
        $this->em->clear();

        $found = $this->em->find(Payslip::class, $id);
        $this->assertNotNull($found);
        $this->assertSame(3, $found->getMonth());
        $this->assertSame(2026, $found->getYear());
    }

    // --- ExchangeRate ---

    public function testCreateExchangeRate(): void
    {
        $er = new ExchangeRate();
        $er->setFromCurrency('USD');
        $er->setToCurrency('TND');
        $er->setRate('3.1250');
        $er->setRateDate(new \DateTimeImmutable('2026-04-15'));
        $er->setSource('BCT');
        $this->em->persist($er);
        $this->em->flush();

        $this->assertNotNull($er->getId());
    }

    public function testReadExchangeRate(): void
    {
        $er = new ExchangeRate();
        $er->setFromCurrency('EUR');
        $er->setToCurrency('TND');
        $er->setRate('3.3480');
        $er->setRateDate(new \DateTimeImmutable('2026-04-15'));
        $this->em->persist($er);
        $this->em->flush();
        $id = $er->getId();
        $this->em->clear();

        $found = $this->em->find(ExchangeRate::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('EUR', $found->getFromCurrency());
    }
}

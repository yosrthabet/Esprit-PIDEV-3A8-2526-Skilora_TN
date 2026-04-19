<?php

namespace App\Tests\Entity\Finance;

use App\Entity\Finance\Contract;
use App\Entity\User;
use App\Recruitment\Entity\Company;
use PHPUnit\Framework\TestCase;

class ContractTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('contractuser');
        $u->setEmail('contract@test.com');
        $u->setFullName('Contract User');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    private function makeCompany(): Company
    {
        $c = new Company();
        $c->setName('TestCorp');

        return $c;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new Contract())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $ct = new Contract();
        $ct->setUser($this->makeUser());
        $ct->setCompany($this->makeCompany());
        $ct->setType('CDI');
        $ct->setPosition('Developer');
        $ct->setSalary(3500.00);
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2025-12-31');
        $ct->setStartDate($start);
        $ct->setEndDate($end);
        $ct->setStatus('ACTIVE');

        $this->assertSame('CDI', $ct->getType());
        $this->assertSame('Developer', $ct->getPosition());
        $this->assertSame(3500.00, $ct->getSalary());
        $this->assertSame($start, $ct->getStartDate());
        $this->assertSame($end, $ct->getEndDate());
        $this->assertSame('ACTIVE', $ct->getStatus());
        $this->assertNotNull($ct->getUser());
        $this->assertNotNull($ct->getCompany());
    }

    public function testNullDefaults(): void
    {
        $ct = new Contract();
        $this->assertNull($ct->getType());
        $this->assertNull($ct->getPosition());
        $this->assertNull($ct->getSalary());
        $this->assertNull($ct->getStartDate());
        $this->assertNull($ct->getEndDate());
        $this->assertNull($ct->getStatus());
    }
}

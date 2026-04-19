<?php

namespace App\Tests\Entity\Finance;

use App\Entity\Finance\BankAccount;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BankAccountTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('bankuser');
        $u->setEmail('bank@test.com');
        $u->setFullName('Bank User');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new BankAccount())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $ba = new BankAccount();
        $ba->setUser($this->makeUser());
        $ba->setBankName('BIAT');
        $ba->setIban('TN5910006035183598983943');
        $ba->setSwift('BABORUTUTR');
        $ba->setCurrency('TND');
        $ba->setIsPrimary(true);
        $ba->setIsVerified(false);

        $this->assertSame('BIAT', $ba->getBankName());
        $this->assertSame('TN5910006035183598983943', $ba->getIban());
        $this->assertSame('BABORUTUTR', $ba->getSwift());
        $this->assertSame('TND', $ba->getCurrency());
        $this->assertTrue($ba->isPrimary());
        $this->assertFalse($ba->isVerified());
        $this->assertNotNull($ba->getUser());
    }

    public function testDefaultFlags(): void
    {
        $ba = new BankAccount();
        $this->assertFalse($ba->isPrimary());
        $this->assertFalse($ba->isVerified());
    }

    public function testNullableFieldsDefault(): void
    {
        $ba = new BankAccount();
        $this->assertNull($ba->getBankName());
        $this->assertNull($ba->getIban());
        $this->assertNull($ba->getSwift());
        $this->assertNull($ba->getCurrency());
    }
}

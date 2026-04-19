<?php

namespace App\Tests\Entity\Finance;

use App\Entity\Finance\Bonus;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BonusTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('bonususer');
        $u->setEmail('bonus@test.com');
        $u->setFullName('Bonus User');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new Bonus())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $b = new Bonus();
        $b->setUser($this->makeUser());
        $b->setAmount(500.0);
        $b->setReason('Top performer');
        $d = new \DateTimeImmutable('2024-06-15');
        $b->setDateAwarded($d);

        $this->assertSame(500.0, $b->getAmount());
        $this->assertSame('Top performer', $b->getReason());
        $this->assertSame($d, $b->getDateAwarded());
        $this->assertNotNull($b->getUser());
    }

    public function testNullDefaults(): void
    {
        $b = new Bonus();
        $this->assertNull($b->getAmount());
        $this->assertNull($b->getReason());
        $this->assertNull($b->getDateAwarded());
    }
}

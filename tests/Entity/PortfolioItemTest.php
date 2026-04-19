<?php

namespace App\Tests\Entity;

use App\Entity\PortfolioItem;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PortfolioItemTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('portuser');
        $u->setEmail('port@test.com');
        $u->setFullName('Port User');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new PortfolioItem())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $p = new PortfolioItem();
        $p->setUser($this->makeUser());
        $p->setTitle('My Project');
        $p->setDescription('A portfolio piece');
        $p->setProjectUrl('https://github.com/test');
        $p->setImageUrl('https://img.test/pic.jpg');
        $p->setTechnologies('PHP, Symfony, MySQL');
        $start = new \DateTime('2023-01-01');
        $p->setStartDate($start);
        $p->setIsFeatured(true);

        $this->assertSame('My Project', $p->getTitle());
        $this->assertSame('A portfolio piece', $p->getDescription());
        $this->assertSame('https://github.com/test', $p->getProjectUrl());
        $this->assertSame('https://img.test/pic.jpg', $p->getImageUrl());
        $this->assertSame('PHP, Symfony, MySQL', $p->getTechnologies());
        $this->assertSame($start, $p->getStartDate());
        $this->assertTrue($p->isFeatured());
        $this->assertNotNull($p->getUser());
    }

    public function testNullDefaults(): void
    {
        $p = new PortfolioItem();
        $this->assertNull($p->getDescription());
        $this->assertNull($p->getProjectUrl());
        $this->assertNull($p->getImageUrl());
        $this->assertNull($p->getTechnologies());
    }
}

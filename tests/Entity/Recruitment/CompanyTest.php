<?php

namespace App\Tests\Entity\Recruitment;

use App\Entity\User;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    public function testNewIdIsNull(): void
    {
        $this->assertNull((new Company())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $c = new Company();
        $c->setName('Sofrecom Tunisie');
        $c->setCountry('Tunisia');
        $c->setIndustry('IT Services');
        $c->setWebsite('https://sofrecom.tn');
        $c->setLogoUrl('https://img.test/logo.png');
        $c->setIsVerified(true);
        $c->setSize('100-500');

        $this->assertSame('Sofrecom Tunisie', $c->getName());
        $this->assertSame('Tunisia', $c->getCountry());
        $this->assertSame('IT Services', $c->getIndustry());
        $this->assertSame('https://sofrecom.tn', $c->getWebsite());
        $this->assertSame('https://img.test/logo.png', $c->getLogoUrl());
        $this->assertTrue($c->isVerified());
        $this->assertSame('100-500', $c->getSize());
    }

    public function testOwner(): void
    {
        $u = new User();
        $u->setUsername('employer');
        $u->setEmail('e@test.com');
        $u->setFullName('Employer');
        $u->setRole('EMPLOYER');
        $u->setPassword('p');

        $c = new Company();
        $c->setOwner($u);
        $this->assertSame($u, $c->getOwner());
    }

    public function testDefaultVerified(): void
    {
        $c = new Company();
        $this->assertFalse($c->isVerified());
    }

    public function testAddJobOffer(): void
    {
        $c = new Company();
        $c->setName('TestCo');

        $j = new JobOffer();
        $j->setTitle('Dev');
        $j->setCurrency('EUR');
        $j->setStatus('OPEN');
        $c->addJobOffer($j);

        $this->assertCount(1, $c->getJobOffers());
        $this->assertSame($c, $j->getCompany());
    }

    public function testEmptyJobOffers(): void
    {
        $c = new Company();
        $this->assertCount(0, $c->getJobOffers());
    }
}

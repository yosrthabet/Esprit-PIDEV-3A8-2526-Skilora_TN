<?php

namespace App\Tests\Entity\Recruitment;

use App\Entity\User;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use PHPUnit\Framework\TestCase;

class JobOfferTest extends TestCase
{
    private function makeCompany(): Company
    {
        $c = new Company();
        $c->setName('Sofrecom');
        $c->setCountry('Tunisia');
        $c->setIndustry('IT');

        return $c;
    }

    private function createJobOffer(array $o = []): JobOffer
    {
        $j = new JobOffer();
        $j->setCompany($o['company'] ?? $this->makeCompany());
        $j->setTitle($o['title'] ?? 'PHP Developer');
        $j->setDescription($o['desc'] ?? 'Build web apps');
        $j->setLocation($o['location'] ?? 'Tunis');
        $j->setWorkType($o['workType'] ?? 'REMOTE');
        $j->setStatus($o['status'] ?? 'OPEN');
        $j->setCurrency($o['currency'] ?? 'TND');

        return $j;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new JobOffer())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $j = $this->createJobOffer();

        $this->assertSame('PHP Developer', $j->getTitle());
        $this->assertSame('Build web apps', $j->getDescription());
        $this->assertSame('Tunis', $j->getLocation());
        $this->assertSame('REMOTE', $j->getWorkType());
        $this->assertSame('OPEN', $j->getStatus());
        $this->assertSame('TND', $j->getCurrency());
        $this->assertNotNull($j->getCompany());
    }

    public function testSalaryRange(): void
    {
        $j = new JobOffer();
        $j->setMinSalary('2000.00');
        $j->setMaxSalary('4000.00');

        $this->assertSame('2000.00', $j->getMinSalary());
        $this->assertSame('4000.00', $j->getMaxSalary());
    }

    public function testFeaturedAndCounts(): void
    {
        $j = new JobOffer();
        $j->setIsFeatured(true);
        $j->setViewsCount(100);
        $j->setApplicationsCount(5);

        $this->assertTrue($j->isFeatured());
        $this->assertSame(100, $j->getViewsCount());
        $this->assertSame(5, $j->getApplicationsCount());
    }

    public function testDefaultCounts(): void
    {
        $j = new JobOffer();
        $this->assertFalse($j->isFeatured());
        $this->assertSame(0, $j->getViewsCount());
        $this->assertSame(0, $j->getApplicationsCount());
    }

    public function testSkillsAndBenefits(): void
    {
        $j = new JobOffer();
        $j->setSkillsRequired('PHP, Symfony, MySQL');
        $j->setBenefits('Remote work, Health insurance');

        $this->assertSame('PHP, Symfony, MySQL', $j->getSkillsRequired());
        $this->assertSame('Remote work, Health insurance', $j->getBenefits());
    }

    public function testCompanyName(): void
    {
        $j = new JobOffer();
        $j->setCompanyName('TestCo');
        $this->assertSame('TestCo', $j->getCompanyName());
    }

    public function testIsOwnedBy(): void
    {
        $owner = new User();
        $owner->setUsername('employer');
        $owner->setEmail('e@test.com');
        $owner->setFullName('Employer');
        $owner->setRole('EMPLOYER');
        $owner->setPassword('p');

        $company = new Company();
        $company->setName('TestCo');
        $company->setOwner($owner);

        $j = new JobOffer();
        $j->setCompany($company);

        // isOwnedBy compares User object
        $this->assertTrue($j->isOwnedBy($owner));
    }
}

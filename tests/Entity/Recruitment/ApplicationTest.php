<?php

namespace App\Tests\Entity\Recruitment;

use App\Entity\User;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobInterview;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\InterviewFormat;
use App\Recruitment\InterviewLifecycle;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private function makeJobOffer(): JobOffer
    {
        $c = new Company();
        $c->setName('TestCo');
        $j = new JobOffer();
        $j->setCompany($c);
        $j->setTitle('Dev');
        $j->setCurrency('EUR');
        $j->setStatus('OPEN');

        return $j;
    }

    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('candidate');
        $u->setEmail('cand@test.com');
        $u->setFullName('Candidate');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new Application())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $a = new Application();
        $a->setJobOffer($this->makeJobOffer());
        $a->setCandidate($this->makeUser());
        $a->setCandidateProfileId(10);
        $a->setCvPath('/uploads/cv.pdf');
        $a->setCoverLetter('I am a great fit');
        $a->setStatus('PENDING');

        $this->assertSame('PENDING', $a->getStatus());
        $this->assertSame(10, $a->getCandidateProfileId());
        $this->assertSame('/uploads/cv.pdf', $a->getCvPath());
        $this->assertSame('I am a great fit', $a->getCoverLetter());
        $this->assertNotNull($a->getJobOffer());
        $this->assertNotNull($a->getCandidate());
    }

    public function testDefaultStatus(): void
    {
        $a = new Application();
        // The default is set in status getter or DB
        $this->assertSame('PENDING', $a->getStatus());
    }

    public function testAppliedAt(): void
    {
        $a = new Application();
        $this->assertInstanceOf(\DateTimeImmutable::class, $a->getAppliedAt());
    }

    public function testMatchAndScore(): void
    {
        $a = new Application();
        $a->setMatchPercentage('85.50');
        $a->setCandidateScore(92);

        $this->assertSame('85.50', $a->getMatchPercentage());
        $this->assertSame(92, $a->getCandidateScore());
    }

    public function testIsAccepted(): void
    {
        $a = new Application();
        $a->setStatus('ACCEPTED');
        $this->assertTrue($a->isAccepted());

        $a->setStatus('PENDING');
        $this->assertFalse($a->isAccepted());
    }

    public function testInterview(): void
    {
        $a = new Application();
        $i = new JobInterview();
        $a->setInterview($i);
        $this->assertSame($i, $a->getInterview());
    }

    public function testStatusLabelFr(): void
    {
        $a = new Application();
        $a->setStatus('PENDING');
        $label = $a->getStatusLabelFr();
        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }
}

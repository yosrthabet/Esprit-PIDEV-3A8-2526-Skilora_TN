<?php

namespace App\Tests\Entity\Recruitment;

use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobInterview;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\InterviewFormat;
use App\Recruitment\InterviewLifecycle;
use PHPUnit\Framework\TestCase;

class JobInterviewTest extends TestCase
{
    private function makeApplication(): Application
    {
        $c = new Company();
        $c->setName('TestCo');
        $j = new JobOffer();
        $j->setCompany($c);
        $j->setTitle('Dev');
        $j->setCurrency('EUR');
        $j->setStatus('OPEN');

        $a = new Application();
        $a->setJobOffer($j);
        $a->setCvPath('/cv.pdf');

        return $a;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new JobInterview())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $i = new JobInterview();
        $i->setApplication($this->makeApplication());
        $date = new \DateTimeImmutable('2026-05-01 10:00:00');
        $i->setScheduledAt($date);
        $i->setDurationMinutes(45);
        $i->setFormat(InterviewFormat::ONSITE);
        $i->setLocation('Office Tunis');

        $this->assertSame($date, $i->getScheduledAt());
        $this->assertSame(45, $i->getDurationMinutes());
        $this->assertSame(InterviewFormat::ONSITE, $i->getFormat());
        $this->assertSame('Office Tunis', $i->getLocation());
        $this->assertNotNull($i->getApplication());
    }

    public function testDefaultFormat(): void
    {
        $i = new JobInterview();
        $this->assertSame(InterviewFormat::ONLINE, $i->getFormat());
    }

    public function testDefaultLifecycleStatus(): void
    {
        $i = new JobInterview();
        $this->assertSame(InterviewLifecycle::SCHEDULED, $i->getLifecycleStatus());
    }

    public function testVideoLink(): void
    {
        $i = new JobInterview();
        $i->setVideoLink('https://meet.google.com/abc-def-ghi');
        $this->assertSame('https://meet.google.com/abc-def-ghi', $i->getVideoLink());
    }

    public function testNotesAndFeedback(): void
    {
        $i = new JobInterview();
        $i->setNotes('Candidate was punctual');
        $i->setFeedback('Strong technical skills');
        $i->setRating(4);

        $this->assertSame('Candidate was punctual', $i->getNotes());
        $this->assertSame('Strong technical skills', $i->getFeedback());
        $this->assertSame(4, $i->getRating());
    }

    public function testTimezone(): void
    {
        $i = new JobInterview();
        $i->setTimezone('Africa/Tunis');
        $this->assertSame('Africa/Tunis', $i->getTimezone());
    }

    public function testCreatedAtOnConstruction(): void
    {
        $i = new JobInterview();
        $this->assertInstanceOf(\DateTimeImmutable::class, $i->getCreatedAt());
    }
}

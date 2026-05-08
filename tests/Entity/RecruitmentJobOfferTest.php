<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Enum\FeedSource;
use App\Enum\JobOfferStatus;
use App\Enum\WorkType;
use App\Recruitment\Entity\JobOffer;
use PHPUnit\Framework\TestCase;

final class RecruitmentJobOfferTest extends TestCase
{
    public function testJobOfferDefaultsAndLabels(): void
    {
        $job = (new JobOffer())
            ->setTitle('Symfony Developer')
            ->setCompanyName('Skilora Labs')
            ->setWorkType(WorkType::REMOTE)
            ->setFeedSource(FeedSource::ANETI)
            ->setSourceQuality(120);

        self::assertSame('Symfony Developer', $job->getTitle());
        self::assertSame('Skilora Labs', $job->getCompanyLabel());
        self::assertSame(JobOfferStatus::OPEN, $job->getStatus());
        self::assertSame(100, $job->getSourceQuality());
        self::assertTrue($job->isExternal());
    }

    public function testReadinessScoreReportsMissingPostingDetails(): void
    {
        $job = (new JobOffer())->setTitle('Dev');

        $readiness = $job->getReadiness();

        self::assertLessThan(41, $readiness['score']);
        self::assertSame('Weak posting', $readiness['label']);
        self::assertContains('Detailed description', $readiness['missing']);
    }
}

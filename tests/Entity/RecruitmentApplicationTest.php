<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Enum\ApplicationStatus;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\JobOffer;
use PHPUnit\Framework\TestCase;

final class RecruitmentApplicationTest extends TestCase
{
    public function testApplicationStoresMatchContext(): void
    {
        $user = (new User())->setUsername('candidate')->setRole('USER');
        $job = (new JobOffer())->setTitle('React Developer');
        $application = (new Application())
            ->setCandidate($user)
            ->setJobOffer($job)
            ->setMatchScore(85)
            ->setMatchReasons(['Matched React', 'Fresh listing']);

        self::assertSame($user, $application->getCandidate());
        self::assertSame($job, $application->getJobOffer());
        self::assertSame(85, $application->getMatchScore());
        self::assertSame(['Matched React', 'Fresh listing'], $application->getMatchReasons());
        self::assertSame(ApplicationStatus::APPLIED, $application->getStatus());
    }
}

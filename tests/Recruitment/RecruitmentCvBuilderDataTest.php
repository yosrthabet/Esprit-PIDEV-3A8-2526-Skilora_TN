<?php

declare(strict_types=1);

namespace App\Tests\Recruitment;

use App\Recruitment\CvBuilder\CvBuilderData;
use PHPUnit\Framework\TestCase;

final class RecruitmentCvBuilderDataTest extends TestCase
{
    public function testSkillsAndLanguagesAreNormalizedLists(): void
    {
        $data = new CvBuilderData(
            'Jane Candidate',
            'Symfony Developer',
            'jane@example.test',
            null,
            null,
            'Builds reliable web applications.',
            [],
            [],
            "PHP, Symfony\nMySQL; PHP",
            "French\nEnglish; French",
            'modern',
            null,
        );

        self::assertSame(['PHP', 'Symfony', 'MySQL'], $data->skillsAsList());
        self::assertSame(['French', 'English'], $data->languagesAsList());
    }
}

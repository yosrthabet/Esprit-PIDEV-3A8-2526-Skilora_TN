<?php

declare(strict_types=1);

namespace App\Recruitment\Twig;

use App\Enum\ExperienceLevel;
use App\Recruitment\WorkTypeCatalog;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class RecruitmentExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('work_type_label', [WorkTypeCatalog::class, 'labelFr']),
            new TwigFilter('experience_level_label', [self::class, 'experienceLevelLabel']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('work_type_labels', [WorkTypeCatalog::class, 'labelsFr']),
        ];
    }

    public static function experienceLevelLabel(?ExperienceLevel $level): string
    {
        return $level?->labelFr() ?? '—';
    }
}

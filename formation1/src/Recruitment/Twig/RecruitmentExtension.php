<?php

declare(strict_types=1);

namespace App\Recruitment\Twig;

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
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('work_type_labels', [WorkTypeCatalog::class, 'labelsFr']),
        ];
    }
}

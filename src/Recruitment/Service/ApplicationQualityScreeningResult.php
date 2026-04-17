<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

final readonly class ApplicationQualityScreeningResult
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        public int $riskScore,
        public bool $blocked,
        public array $reasons,
    ) {
    }
}


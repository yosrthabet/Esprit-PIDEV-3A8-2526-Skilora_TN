<?php

declare(strict_types=1);

namespace App\Recruitment;

final class InterviewFormat
{
    public const ONLINE = 'ONLINE';

    public const ONSITE = 'ONSITE';

    public static function isValid(string $value): bool
    {
        return $value === self::ONLINE || $value === self::ONSITE;
    }

    public static function labelFr(string $format): string
    {
        return match ($format) {
            self::ONSITE => 'En personne',
            default => 'En ligne',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return [
            self::ONLINE => 'En ligne',
            self::ONSITE => 'Présentiel (sur place)',
        ];
    }
}

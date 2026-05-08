<?php

declare(strict_types=1);

namespace App\Enum;

enum ExperienceLevel: string
{
    case JUNIOR = 'junior';
    case MID = 'mid';
    case SENIOR = 'senior';
    case LEAD = 'lead';
    case INTERN = 'intern';

    public function labelFr(): string
    {
        return match ($this) {
            self::INTERN => 'Stagiaire',
            self::JUNIOR => 'Junior',
            self::MID => 'Confirmé',
            self::SENIOR => 'Senior',
            self::LEAD => 'Lead / Expert',
        };
    }

    /**
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return [self::INTERN, self::JUNIOR, self::MID, self::SENIOR, self::LEAD];
    }
}

<?php

declare(strict_types=1);

namespace App\Enum;

enum ApplicationStatus: string
{
    case APPLIED = 'applied';
    case VIEWED = 'viewed';
    case INTERVIEW = 'interview';
    case OFFER = 'offer';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::APPLIED => 'Applied',
            self::VIEWED => 'Viewed',
            self::INTERVIEW => 'Interview',
            self::OFFER => 'Offer',
            self::REJECTED => 'Rejected',
            self::WITHDRAWN => 'Withdrawn',
        };
    }
}

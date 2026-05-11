<?php

declare(strict_types=1);

namespace App\Enum;

enum ApplicationStatus: string
{
    case APPLIED = 'applied';
    case VIEWED = 'viewed';
    case INTERVIEW = 'interview';
    case OFFER = 'offer';
    case HIRED = 'hired';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::APPLIED => 'Applied',
            self::VIEWED => 'Viewed',
            self::INTERVIEW => 'Interview',
            self::OFFER => 'Offer',
            self::HIRED => 'Hired',
            self::REJECTED => 'Rejected',
            self::WITHDRAWN => 'Withdrawn',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::APPLIED => 'bg-blue-500/15 text-blue-400',
            self::VIEWED => 'bg-cyan-500/15 text-cyan-400',
            self::INTERVIEW => 'bg-amber-500/15 text-amber-400',
            self::OFFER => 'bg-purple-500/15 text-purple-400',
            self::HIRED => 'bg-emerald-500/15 text-emerald-400',
            self::REJECTED => 'bg-destructive/15 text-destructive',
            self::WITHDRAWN => 'bg-zinc-500/15 text-zinc-400',
        };
    }
}

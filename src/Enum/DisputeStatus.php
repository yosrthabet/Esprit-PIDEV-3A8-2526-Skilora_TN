<?php

declare(strict_types=1);

namespace App\Enum;

enum DisputeStatus: string
{
    case OPEN = 'open';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::RESOLVED => 'Resolved',
        };
    }
}

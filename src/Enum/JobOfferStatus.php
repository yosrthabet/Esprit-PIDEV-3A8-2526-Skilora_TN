<?php

declare(strict_types=1);

namespace App\Enum;

enum JobOfferStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case FILLED = 'filled';
    case CLOSED = 'closed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::OPEN => 'Open',
            self::FILLED => 'Filled',
            self::CLOSED => 'Closed',
            self::EXPIRED => 'Expired',
        };
    }
}

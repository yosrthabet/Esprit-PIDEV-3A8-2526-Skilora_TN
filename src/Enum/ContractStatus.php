<?php

declare(strict_types=1);

namespace App\Enum;

enum ContractStatus: string
{
    case PENDING_FUNDING = 'pending_funding';
    case ACTIVE = 'active';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case DISPUTED = 'disputed';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_FUNDING => 'Pending funding',
            self::ACTIVE => 'Active',
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::DISPUTED => 'Disputed',
            self::CLOSED => 'Closed',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enum;

enum PayoutStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
}

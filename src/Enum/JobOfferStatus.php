<?php

declare(strict_types=1);

namespace App\Enum;

enum JobOfferStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSED = 'closed';
    case EXPIRED = 'expired';
}

<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

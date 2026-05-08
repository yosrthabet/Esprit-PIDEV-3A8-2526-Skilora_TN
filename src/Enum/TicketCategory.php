<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketCategory: string
{
    case ACCOUNT = 'account';
    case RECRUITMENT = 'recruitment';
    case FINANCE = 'finance';
    case TECHNICAL = 'technical';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ACCOUNT => 'Account',
            self::RECRUITMENT => 'Recruitment',
            self::FINANCE => 'Finance',
            self::TECHNICAL => 'Technical',
            self::OTHER => 'Other',
        };
    }
}

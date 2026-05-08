<?php

declare(strict_types=1);

namespace App\Enum;

enum EscrowTransactionType: string
{
    case FUND = 'fund';
    case RELEASE = 'release';
    case REFUND = 'refund';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

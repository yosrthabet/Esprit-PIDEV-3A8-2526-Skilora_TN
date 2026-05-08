<?php

declare(strict_types=1);

namespace App\Enum;

enum Currency: string
{
    case TND = 'TND';
    case EUR = 'EUR';
    case USD = 'USD';
}

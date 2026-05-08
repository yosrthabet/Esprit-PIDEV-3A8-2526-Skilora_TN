<?php

declare(strict_types=1);

namespace App\Enum;

enum LoginMethod: string
{
    case PASSWORD = 'password';
    case GOOGLE = 'google';
    case GITHUB = 'github';
    case PASSKEY = 'passkey';
}

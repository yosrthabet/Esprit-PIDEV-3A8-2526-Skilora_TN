<?php

declare(strict_types=1);

namespace App\Enum;

enum LoginStatus: string
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';
}

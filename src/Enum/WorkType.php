<?php

declare(strict_types=1);

namespace App\Enum;

enum WorkType: string
{
    case REMOTE = 'remote';
    case ONSITE = 'onsite';
    case HYBRID = 'hybrid';
}

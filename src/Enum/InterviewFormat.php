<?php

declare(strict_types=1);

namespace App\Enum;

enum InterviewFormat: string
{
    case ONLINE = 'online';
    case ONSITE = 'onsite';
    case PHONE = 'phone';
}

<?php

declare(strict_types=1);

namespace App\Enum;

enum FeedSource: string
{
    case PLATFORM = 'platform';
    case ANETI = 'aneti';
    case REDDIT = 'reddit';
    case RSS = 'rss';
    case LINKEDIN_RSS = 'linkedin_rss';
}

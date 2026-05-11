<?php

declare(strict_types=1);

namespace App\Community;

enum CommunityReportStatus: string
{
    case OPEN = 'open';
    case REVIEWED = 'reviewed';
    case DISMISSED = 'dismissed';
}

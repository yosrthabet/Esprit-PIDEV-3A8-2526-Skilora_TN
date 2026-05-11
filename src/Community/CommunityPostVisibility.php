<?php

declare(strict_types=1);

namespace App\Community;

enum CommunityPostVisibility: string
{
    case PUBLIC = 'public';
    case CONNECTIONS = 'connections';
    case PRIVATE = 'private';
}

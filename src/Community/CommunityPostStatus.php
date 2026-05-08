<?php

declare(strict_types=1);

namespace App\Community;

enum CommunityPostStatus: string
{
    case PUBLISHED = 'published';
    case PENDING_REVIEW = 'pending_review';
    case REJECTED = 'rejected';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::PUBLISHED => 'Published',
            self::PENDING_REVIEW => 'Pending review',
            self::REJECTED => 'Rejected',
            self::DELETED => 'Deleted',
        };
    }
}

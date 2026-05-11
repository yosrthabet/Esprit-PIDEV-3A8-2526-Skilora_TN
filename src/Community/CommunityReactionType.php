<?php

declare(strict_types=1);

namespace App\Community;

enum CommunityReactionType: string
{
    case LIKE = 'like';
    case HEART = 'heart';
    case LAUGH = 'laugh';
    case FIRE = 'fire';
    case CLAP = 'clap';
    case WOW = 'wow';

    public function icon(): string
    {
        return match ($this) {
            self::LIKE => '👍',
            self::HEART => '❤️',
            self::LAUGH => '😂',
            self::FIRE => '🔥',
            self::CLAP => '👏',
            self::WOW => '😮',
        };
    }
}

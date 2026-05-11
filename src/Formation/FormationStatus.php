<?php

declare(strict_types=1);

namespace App\Formation;

enum FormationStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case PUBLISHED = 'published';
    case REFUSED = 'refused';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_REVIEW => 'Under Review',
            self::PUBLISHED => 'Published',
            self::REFUSED => 'Refused',
            self::ARCHIVED => 'Archived',
        };
    }

    public function labelFr(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PENDING_REVIEW => 'En revue',
            self::PUBLISHED => 'Publiee',
            self::REFUSED => 'Refusee',
            self::ARCHIVED => 'Archivee',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-muted text-muted-foreground',
            self::PENDING_REVIEW => 'bg-amber-500/15 text-amber-500',
            self::PUBLISHED => 'bg-emerald-500/15 text-emerald-500',
            self::REFUSED => 'bg-destructive/15 text-destructive',
            self::ARCHIVED => 'bg-zinc-500/15 text-zinc-400',
        };
    }
}

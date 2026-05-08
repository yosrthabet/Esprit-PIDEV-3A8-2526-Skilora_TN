<?php

declare(strict_types=1);

namespace App\Formation;

enum FormationStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function labelFr(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publiee',
            self::ARCHIVED => 'Archivee',
        };
    }
}

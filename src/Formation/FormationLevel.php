<?php

declare(strict_types=1);

namespace App\Formation;

enum FormationLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    public function labelFr(): string
    {
        return match ($this) {
            self::BEGINNER => 'Debutant',
            self::INTERMEDIATE => 'Intermediaire',
            self::ADVANCED => 'Avance',
        };
    }
}

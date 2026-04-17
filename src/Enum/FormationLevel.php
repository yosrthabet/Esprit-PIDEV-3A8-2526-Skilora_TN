<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Niveau pédagogique d'une formation (stocké en base comme chaîne ENUM).
 */
enum FormationLevel: string
{
    case BEGINNER = 'BEGINNER';
    case INTERMEDIATE = 'INTERMEDIATE';
    case ADVANCED = 'ADVANCED';

    public function labelFr(): string
    {
        return match ($this) {
            self::BEGINNER => 'Débutant',
            self::INTERMEDIATE => 'Intermédiaire',
            self::ADVANCED => 'Avancé',
        };
    }

    /**
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return [self::BEGINNER, self::INTERMEDIATE, self::ADVANCED];
    }
}

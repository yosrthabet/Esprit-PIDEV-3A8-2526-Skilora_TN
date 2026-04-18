<?php

declare(strict_types=1);

namespace App\Recruitment;

/**
 * Avancement de l’entretien (indépendant du statut de candidature).
 */
final class InterviewLifecycle
{
    public const SCHEDULED = 'SCHEDULED';

    /** Entretien terminé / passé */
    public const COMPLETED = 'COMPLETED';

    public static function isValid(string $value): bool
    {
        return $value === self::SCHEDULED || $value === self::COMPLETED;
    }

    public static function labelFr(string $value): string
    {
        return match ($value) {
            self::COMPLETED => 'Entretien passé',
            default => 'À venir',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return [
            'À venir' => self::SCHEDULED,
            'Entretien passé' => self::COMPLETED,
        ];
    }
}

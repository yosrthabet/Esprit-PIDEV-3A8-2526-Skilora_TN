<?php

declare(strict_types=1);

namespace App\Recruitment;

/**
 * Statuts de candidature : défini par l’employeur, affiché en lecture seule côté candidat.
 */
final class ApplicationStatus
{
    public const IN_PROGRESS = 'IN_PROGRESS';

    public const ACCEPTED = 'ACCEPTED';

    public const REJECTED = 'REJECTED';

    /** @var list<string> */
    private const EMPLOYER_SETTABLE = [
        self::IN_PROGRESS,
        self::ACCEPTED,
        self::REJECTED,
    ];

    public static function isValidEmployerStatus(string $value): bool
    {
        return \in_array($value, self::EMPLOYER_SETTABLE, true);
    }

    /**
     * Valeur stockée en base (après migration des anciens codes).
     */
    public static function normalizeStored(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return self::IN_PROGRESS;
        }

        if ($raw === self::ACCEPTED || $raw === self::REJECTED) {
            return $raw;
        }

        // SUBMITTED, REVIEWED, ou toute autre valeur historique → En cours
        return self::IN_PROGRESS;
    }

    public static function labelFr(string $status): string
    {
        $u = strtoupper(trim($status));
        if ($u === 'INTERVIEW') {
            return 'Entretien';
        }
        if ($u === 'PENDING') {
            return 'En attente';
        }

        return match (self::normalizeStored($status)) {
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Refusé',
            default => 'En cours',
        };
    }

    /**
     * @return array<string, string> value => libellé pour formulaires employeur
     */
    public static function employerChoiceLabels(): array
    {
        return [
            self::IN_PROGRESS => 'En cours',
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Refusé',
        ];
    }
}

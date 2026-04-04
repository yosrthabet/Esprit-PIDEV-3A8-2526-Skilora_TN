<?php

declare(strict_types=1);

namespace App\Recruitment;

/** Modes de travail (`job_offers.work_type`) — recherche / filtres / affichage. */
final class WorkTypeCatalog
{
    /** @var list<string> */
    public const VALUES = ['ONSITE', 'REMOTE', 'PART_TIME', 'FREELANCE', 'INTERNSHIP'];

    /** Libellés FR alignés sur les valeurs stockées en base. */
    public const LABELS_FR = [
        'ONSITE' => 'Sur site',
        'REMOTE' => 'Télétravail',
        'PART_TIME' => 'Temps partiel',
        'FREELANCE' => 'Freelance',
        'INTERNSHIP' => 'Stage',
    ];

    /**
     * @return array<string, string> code => libellé
     */
    public static function labelsFr(): array
    {
        return self::LABELS_FR;
    }

    public static function labelFr(?string $code): string
    {
        if ($code === null || $code === '') {
            return '—';
        }

        return self::LABELS_FR[$code] ?? $code;
    }

    public static function normalizeFilter(?string $raw): ?string
    {
        if ($raw === null || $raw === '' || $raw === 'all') {
            return null;
        }

        return \in_array($raw, self::VALUES, true) ? $raw : null;
    }
}

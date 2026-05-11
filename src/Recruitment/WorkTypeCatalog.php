<?php

declare(strict_types=1);

namespace App\Recruitment;

use App\Enum\WorkType;

final class WorkTypeCatalog
{
    public const LABELS_FR = [
        'ONSITE' => 'Sur site',
        'REMOTE' => 'Télétravail',
        'HYBRID' => 'Hybride',
        'PART_TIME' => 'Temps partiel',
        'FREELANCE' => 'Freelance',
        'INTERNSHIP' => 'Stage',
    ];

    /** @return array<string, string> */
    public static function labelsFr(): array
    {
        return self::LABELS_FR;
    }

    public static function labelFr(WorkType|string|null $code): string
    {
        if ($code instanceof WorkType) {
            $code = strtoupper($code->value);
        }

        if ($code === null || $code === '') {
            return '—';
        }

        return self::LABELS_FR[strtoupper($code)] ?? (string) $code;
    }

    public static function normalizeFilter(?string $raw): ?string
    {
        if ($raw === null || $raw === '' || $raw === 'all') {
            return null;
        }

        $upper = strtoupper($raw);

        return isset(self::LABELS_FR[$upper]) ? $upper : null;
    }
}

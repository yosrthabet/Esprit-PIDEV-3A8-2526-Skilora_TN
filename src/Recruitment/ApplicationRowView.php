<?php

declare(strict_types=1);

namespace App\Recruitment;

/**
 * Enrichit une ligne {@code applications} (clés SQL normalisées) pour les templates.
 *
 * @param array<string, mixed> $row
 *
 * @return array<string, mixed>
 */
final class ApplicationRowView
{
    public static function forTwig(array $row): array
    {
        $st = $row['status'] ?? null;
        $stStr = \is_string($st) ? $st : '';

        return array_merge($row, [
            'id' => isset($row['id']) ? (int) $row['id'] : null,
            'statusRaw' => $st,
            'statusLabelFr' => ApplicationStatus::labelFr($stStr !== '' ? $stStr : 'PENDING'),
            'appliedAt' => $row['applied_at'] ?? null,
            'appliedDate' => $row['applied_date'] ?? null,
            'candidateProfileId' => isset($row['candidate_profile_id']) ? (int) $row['candidate_profile_id'] : null,
            'cvPath' => \is_string($row['cv_path'] ?? null) ? $row['cv_path'] : '',
            'coverLetter' => $row['cover_letter'] ?? null,
            'customCvUrl' => $row['custom_cv_url'] ?? null,
            'matchPercentage' => $row['match_percentage'] ?? null,
            'candidateScore' => isset($row['candidate_score']) && $row['candidate_score'] !== null && $row['candidate_score'] !== ''
                ? (int) $row['candidate_score']
                : null,
            'status' => $stStr !== '' ? $stStr : 'PENDING',
        ]);
    }

    /**
     * Valeurs prêtes à afficher pour chaque colonne SQL (dates formatées, texte tronqué).
     *
     * @param array<string, mixed> $viewRow sortie {@see forTwig()}
     * @param list<string>         $columns ordre des colonnes (ex. schéma)
     *
     * @return array<string, string>
     */
    public static function cellsForColumns(array $viewRow, array $columns): array
    {
        $cells = [];
        foreach ($columns as $col) {
            $v = $viewRow[$col] ?? null;
            if ($v instanceof \DateTimeInterface) {
                $cells[$col] = $v->format('Y-m-d H:i:s');
            } elseif ($v === null) {
                $cells[$col] = 'NULL';
            } elseif (\is_string($v) && \strlen($v) > 500) {
                $cells[$col] = \substr($v, 0, 500).'… ('.\strlen($v).' car.)';
            } elseif (\is_bool($v)) {
                $cells[$col] = $v ? 'true' : 'false';
            } elseif (\is_scalar($v)) {
                $cells[$col] = (string) $v;
            } else {
                try {
                    $cells[$col] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                    $cells[$col] = '['.get_debug_type($v).']';
                }
            }
        }

        return $cells;
    }
}

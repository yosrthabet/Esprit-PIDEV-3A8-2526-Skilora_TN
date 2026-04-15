<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Recruitment\ApplicationStatus;
use App\Recruitment\Sql\EmployerApplicationsScopeSql;
use App\Recruitment\ViewModel\EmployerCandidatureListItem;
use App\Recruitment\ViewModel\EmployerCandidatureProfileView;
use Doctrine\DBAL\Connection;

/**
 * Accès exclusif à la table SQL {@code applications} (INSERT / SELECT / UPDATE).
 * Aucune autre source pour les données de candidature.
 */
final class ApplicationsTableGateway
{
    /** @var array<string, true>|null */
    private ?array $applicationsColumnNames = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly bool $employerSeesAllCandidatures = false,
    ) {
    }

    /**
     * Filtre employeur sur les offres : soit toutes les candidatures (mode démo / vue globale),
     * soit uniquement les offres dont l’entreprise appartient à l’employeur.
     *
     * @return array{0: string, 1: list<mixed>} SQL booléen + paramètres à fusionner (sans le {@code ?} de {@code a.id} etc.)
     */
    private function employerJobOfferScope(int $ownerUserId): array
    {
        if ($this->employerSeesAllCandidatures) {
            return ['1=1', []];
        }

        return [EmployerApplicationsScopeSql::jobOfferOwnedByEmployer('j'), [$ownerUserId]];
    }

    /**
     * Noms de colonnes réels de la table {@code applications} (triés), pour affichage complet côté UI.
     *
     * @return list<string>
     */
    public function getApplicationsTableColumnNames(): array
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['applications'])) {
            return [];
        }

        $names = [];
        foreach ($this->connection->createSchemaManager()->listTableColumns('applications') as $c) {
            $names[] = strtolower($c->getName());
        }
        sort($names);

        return $names;
    }

    private function applicationsHasColumn(string $name): bool
    {
        if ($this->applicationsColumnNames === null) {
            $this->applicationsColumnNames = [];
            if (!$this->connection->createSchemaManager()->tablesExist(['applications'])) {
                return false;
            }
            foreach ($this->connection->createSchemaManager()->listTableColumns('applications') as $c) {
                $this->applicationsColumnNames[strtolower($c->getName())] = true;
            }
        }

        return isset($this->applicationsColumnNames[strtolower($name)]);
    }

    public function countByEmployerOwnerUserId(int $ownerUserId): int
    {
        [$where, $params] = $this->employerJobOfferScope($ownerUserId);

        return (int) $this->connection->fetchOne(
            <<<SQL
            SELECT COUNT(a.id)
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE {$where}
            SQL,
            $params,
        );
    }

    /**
     * Liste employeur : uniquement des lignes {@code applications} (table de départ), jamais une liste d’utilisateurs seule.
     * {@code INNER JOIN users} : un candidat n’apparaît que s’il existe une candidature le liant à une offre de l’employeur.
     *
     * @return list<EmployerCandidatureListItem>
     */
    public function fetchEmployerCandidatureListForDisplay(int $ownerUserId, ?string $statusEquals = null): array
    {
        [$whereEmployer, $scopeParams] = $this->employerJobOfferScope($ownerUserId);
        $sql = <<<SQL
            SELECT
                a.id AS application_id,
                a.candidate_profile_id AS candidate_user_id,
                a.applied_date AS applied_at,
                a.status AS status,
                COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—') AS candidate_name,
                u.email AS candidate_email,
                j.id AS job_offer_id,
                j.title AS job_title
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            INNER JOIN users u ON u.id = a.candidate_profile_id
            WHERE {$whereEmployer}
            SQL;
        $params = $scopeParams;
        if ($statusEquals !== null && $statusEquals !== '') {
            $sql .= ' AND a.status = ?';
            $params[] = $statusEquals;
        }
        $sql .= ' ORDER BY a.applied_date DESC, a.id DESC';

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        return array_map(fn (array $r) => $this->mapEmployerListRow($this->lowercaseKeys($r)), $rows);
    }

    /**
     * Répartition des candidatures par compte candidat (même filtre employeur que la liste).
     * Utile pour vérifier qu’il n’y a pas de {@code WHERE candidate_profile_id = ?} caché : si un seul groupe,
     * c’est que les lignes {@code applications} pointent toutes vers le même {@code users.id}.
     *
     * @return list<array{candidate_user_id: int, application_count: int, candidate_name: string, candidate_email: ?string}>
     */
    public function fetchEmployerCandidateBreakdown(int $ownerUserId): array
    {
        [$whereEmployer, $params] = $this->employerJobOfferScope($ownerUserId);
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT
                a.candidate_profile_id AS candidate_user_id,
                COUNT(*) AS application_count,
                MAX(COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—')) AS candidate_name,
                MAX(u.email) AS candidate_email
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            INNER JOIN users u ON u.id = a.candidate_profile_id
            WHERE {$whereEmployer}
            GROUP BY a.candidate_profile_id
            ORDER BY application_count DESC, a.candidate_profile_id ASC
            SQL,
            $params,
        );

        $out = [];
        foreach ($rows as $r) {
            $r = $this->lowercaseKeys($r);
            $out[] = [
                'candidate_user_id' => (int) ($r['candidate_user_id'] ?? 0),
                'application_count' => (int) ($r['application_count'] ?? 0),
                'candidate_name' => \is_string($r['candidate_name'] ?? null) ? $r['candidate_name'] : '—',
                'candidate_email' => isset($r['candidate_email']) && \is_string($r['candidate_email']) && $r['candidate_email'] !== '' ? $r['candidate_email'] : null,
            ];
        }

        return $out;
    }

    public function fetchEmployerCandidatureProfileForEmployer(int $applicationId, int $employerUserId): ?EmployerCandidatureProfileView
    {
        [$whereEmployer, $scopeParams] = $this->employerJobOfferScope($employerUserId);
        $params = array_merge([$applicationId], $scopeParams);
        $row = $this->connection->fetchAssociative(
            <<<SQL
            SELECT
                a.id AS application_id,
                a.applied_date AS applied_at,
                a.status AS status,
                a.cover_letter AS cover_letter,
                COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—') AS candidate_name,
                u.email AS candidate_email,
                j.id AS job_offer_id,
                j.title AS job_title,
                j.work_type AS job_work_type
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            INNER JOIN users u ON u.id = a.candidate_profile_id
            WHERE a.id = ?
              AND ({$whereEmployer})
            SQL,
            $params,
        );

        if (!\is_array($row)) {
            return null;
        }

        $r = $this->lowercaseKeys($row);
        $appliedAt = $this->parseDateTimeImmutable($r['applied_at'] ?? null) ?? new \DateTimeImmutable();
        $raw = \is_string($r['status'] ?? null) ? trim((string) $r['status']) : '';

        $letter = $r['cover_letter'] ?? null;
        if (!\is_string($letter) || trim($letter) === '') {
            $letter = null;
        }

        return new EmployerCandidatureProfileView(
            (int) $r['application_id'],
            \is_string($r['candidate_name'] ?? null) && $r['candidate_name'] !== '' ? $r['candidate_name'] : '—',
            isset($r['candidate_email']) && \is_string($r['candidate_email']) && $r['candidate_email'] !== '' ? $r['candidate_email'] : null,
            (int) $r['job_offer_id'],
            \is_string($r['job_title'] ?? null) ? $r['job_title'] : '—',
            isset($r['job_work_type']) && \is_string($r['job_work_type']) && $r['job_work_type'] !== '' ? $r['job_work_type'] : null,
            $appliedAt,
            $raw !== '' ? $raw : 'IN_PROGRESS',
            ApplicationStatus::labelFr($raw !== '' ? $raw : 'PENDING'),
            $letter,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapEmployerListRow(array $row): EmployerCandidatureListItem
    {
        $appliedAt = $this->parseDateTimeImmutable($row['applied_at'] ?? null) ?? new \DateTimeImmutable();
        $raw = \is_string($row['status'] ?? null) ? trim((string) $row['status']) : '';

        return new EmployerCandidatureListItem(
            (int) $row['application_id'],
            (int) $row['candidate_user_id'],
            \is_string($row['candidate_name'] ?? null) && $row['candidate_name'] !== '' ? $row['candidate_name'] : '—',
            isset($row['candidate_email']) && \is_string($row['candidate_email']) && $row['candidate_email'] !== '' ? $row['candidate_email'] : null,
            (int) $row['job_offer_id'],
            \is_string($row['job_title'] ?? null) ? $row['job_title'] : '—',
            $appliedAt,
            $raw !== '' ? $raw : 'IN_PROGRESS',
            ApplicationStatus::labelFr($raw !== '' ? $raw : 'PENDING'),
        );
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function lowercaseKeys(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $out[strtolower((string) $k)] = $v;
        }

        return $out;
    }

    private function parseDateTimeImmutable(mixed $v): ?\DateTimeImmutable
    {
        if ($v instanceof \DateTimeImmutable) {
            return $v;
        }
        if ($v instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($v);
        }
        if (\is_string($v) && $v !== '') {
            try {
                return new \DateTimeImmutable($v);
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null ligne normalisée (clés minuscules, dates en \DateTimeImmutable quand possible)
     */
    public function fetchById(int $id): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM applications WHERE id = ?',
            [$id],
        );

        return \is_array($row) ? $this->normalizeRow($row) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchByCandidateUserId(int $candidateId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM applications WHERE candidate_profile_id = ? ORDER BY applied_date DESC, id DESC',
            [$candidateId],
        );

        return array_map(fn (array $r) => $this->normalizeRow($r), $rows);
    }

    /**
     * Liste candidat (vue « Mes candidatures ») : une requête avec JOINs, comme {@code getApplicationsByProfile} côté Java.
     * Filtre uniquement {@code candidate_id} ; enrichit titre d’offre, lieu, entreprise.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchCandidateApplicationsWithJobAndCompany(int $candidateUserId): array
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['applications', 'job_offers'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT
                a.*,
                jo.title AS job_title,
                jo.location AS job_location,
                c.name AS company_name
            FROM applications a
            INNER JOIN job_offers jo ON jo.id = a.job_offer_id
            LEFT JOIN companies c ON c.id = jo.company_id
            WHERE a.candidate_profile_id = ?
            ORDER BY a.applied_date DESC, a.id DESC
            SQL,
            [$candidateUserId],
        );

        return array_map(fn (array $r) => $this->normalizeRow($r), $rows);
    }

    /**
     * Suppression par le candidat : uniquement si la ligne lui appartient.
     */
    public function deleteByIdForCandidate(int $applicationId, int $candidateUserId): bool
    {
        return $this->connection->executeStatement(
            'DELETE FROM applications WHERE id = ? AND candidate_profile_id = ?',
            [$applicationId, $candidateUserId],
        ) > 0;
    }

    /**
     * Candidatures visibles employeur : uniquement des lignes {@code applications.*}, filtrées par offre liée.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchByEmployerOwnerUserId(int $ownerUserId): array
    {
        [$whereEmployer, $params] = $this->employerJobOfferScope($ownerUserId);
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT a.*
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE {$whereEmployer}
            ORDER BY a.applied_date DESC, a.id DESC
            SQL,
            $params,
        );

        return array_map(fn (array $r) => $this->normalizeRow($r), $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchAcceptedByEmployerOwnerUserId(int $ownerUserId): array
    {
        [$whereEmployer, $params] = $this->employerJobOfferScope($ownerUserId);
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT a.*
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE {$whereEmployer}
              AND a.status = 'ACCEPTED'
            ORDER BY a.applied_date DESC, a.id DESC
            SQL,
            $params,
        );

        return array_map(fn (array $r) => $this->normalizeRow($r), $rows);
    }

    public function existsForJobOfferAndCandidate(int $jobOfferId, int $candidateId): bool
    {
        $v = $this->connection->fetchOne(
            'SELECT id FROM applications WHERE job_offer_id = ? AND candidate_profile_id = ? LIMIT 1',
            [$jobOfferId, $candidateId],
        );

        return $v !== false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchByJobOfferAndCandidate(int $jobOfferId, int $candidateId): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM applications WHERE job_offer_id = ? AND candidate_profile_id = ? LIMIT 1',
            [$jobOfferId, $candidateId],
        );

        return \is_array($row) ? $this->normalizeRow($row) : null;
    }

    /**
     * Vérifie que la ligne {@code applications.id} est liée à une offre de l’employeur (via sous-requête).
     */
    public function employerOwnsApplication(int $applicationId, int $employerUserId): bool
    {
        if ($this->employerSeesAllCandidatures) {
            $v = $this->connection->fetchOne(
                <<<'SQL'
                SELECT 1
                FROM applications a
                INNER JOIN job_offers j ON j.id = a.job_offer_id
                WHERE a.id = ?
                SQL,
                [$applicationId],
            );

            return $v !== false;
        }

        [$whereEmployer, $params] = $this->employerJobOfferScope($employerUserId);
        $queryParams = array_merge([$applicationId], $params);
        $v = $this->connection->fetchOne(
            <<<SQL
            SELECT 1
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE a.id = ?
              AND ({$whereEmployer})
            SQL,
            $queryParams,
        );

        return $v !== false;
    }

    /**
     * INSERT unique dans {@code applications}. Retourne l’identifiant inséré.
     */
    public function insertApplication(
        int $jobOfferId,
        int $candidateProfileId,
        int $candidateId,
        string $status,
        string $cvPath,
        ?string $coverLetter,
    ): int {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $columns = [];
        $params = [];

        $columns[] = 'job_offer_id';
        $params[] = $jobOfferId;

        $columns[] = 'candidate_profile_id';
        $params[] = $candidateProfileId ?? $candidateId;

        $columns[] = 'status';
        $params[] = $status;

        if ($this->applicationsHasColumn('cv_path')) {
            $columns[] = 'cv_path';
            $params[] = $cvPath;
        }

        $columns[] = 'cover_letter';
        $params[] = $coverLetter;

        $columns[] = 'applied_date';
        $params[] = $now;

        if ($this->applicationsHasColumn('custom_cv_url')) {
            $columns[] = 'custom_cv_url';
            $params[] = null;
        }

        if ($this->applicationsHasColumn('match_percentage')) {
            $columns[] = 'match_percentage';
            $params[] = null;
        }

        if ($this->applicationsHasColumn('candidate_score')) {
            $columns[] = 'candidate_score';
            $params[] = null;
        }

        $placeholders = implode(', ', array_fill(0, \count($columns), '?'));
        $sql = 'INSERT INTO applications ('.implode(', ', $columns).') VALUES ('.$placeholders.')';

        $this->connection->executeStatement($sql, $params);

        return (int) $this->connection->lastInsertId();
    }

    public function updateStatus(int $applicationId, string $status): void
    {
        $this->connection->executeStatement(
            'UPDATE applications SET status = ? WHERE id = ?',
            [$status, $applicationId],
        );
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $out[strtolower((string) $k)] = $v;
        }
        foreach (['applied_at', 'applied_date'] as $dk) {
            if (isset($out[$dk]) && \is_string($out[$dk]) && $out[$dk] !== '') {
                try {
                    $out[$dk] = new \DateTimeImmutable($out[$dk]);
                } catch (\Throwable) {
                }
            }
        }

        return $out;
    }
}

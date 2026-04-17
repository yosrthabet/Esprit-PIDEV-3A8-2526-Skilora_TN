<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Recruitment\ApplicationStatus;
use App\Recruitment\Service\ApplicationsTableSchemaPatcher;
use App\Recruitment\Sql\ApplicationCandidateJoinParts;
use App\Recruitment\Sql\EmployerApplicationsScopeSql;
use App\Recruitment\ViewModel\EmployerCandidatureListItem;
use App\Recruitment\ViewModel\EmployerCandidatureProfileView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

/**
 * Accès exclusif à la table SQL {@code applications} (INSERT / SELECT / UPDATE).
 * Aucune autre source pour les données de candidature.
 */
final class ApplicationsTableGateway
{
    /** @var array<string, true>|null */
    private ?array $applicationsColumnNames = null;

    private static bool $candidateUserLinkBootstrapped = false;

    public function __construct(
        private readonly Connection $connection,
        private readonly ApplicationsTableSchemaPatcher $schemaPatcher,
        private readonly bool $employerSeesAllCandidatures = false,
    ) {
    }

    private function sqlAppliedAtExpression(string $tableAlias = 'a'): string
    {
        return ApplicationCandidateJoinParts::sqlCoalesceAppliedTimestamp($this->connection, $tableAlias);
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

    /**
     * Ajoute {@code applications.candidate_id} (users.id) si elle manque, puis remplit depuis {@code profiles} / legacy.
     * Sans cette colonne, « Mes candidatures » ne peut pas filtrer de façon fiable.
     */
    private function bootstrapCandidateUserLinkIfNeeded(): void
    {
        if (self::$candidateUserLinkBootstrapped) {
            return;
        }
        self::$candidateUserLinkBootstrapped = true;

        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['applications'])) {
            return;
        }

        if (!$this->applicationsHasColumn('candidate_id')) {
            $platform = $this->connection->getDatabasePlatform();
            try {
                if ($platform instanceof AbstractMySQLPlatform) {
                    $this->connection->executeStatement(
                        'ALTER TABLE applications ADD COLUMN candidate_id INT NULL DEFAULT NULL',
                    );
                } elseif ($platform instanceof SQLitePlatform) {
                    $this->connection->executeStatement(
                        'ALTER TABLE applications ADD COLUMN candidate_id INTEGER NULL',
                    );
                } else {
                    $this->connection->executeStatement(
                        'ALTER TABLE applications ADD COLUMN candidate_id INTEGER NULL',
                    );
                }
            } catch (\Throwable) {
                // Colonne déjà ajoutée (concurrence) ou plateforme spécifique
            }
            $this->applicationsColumnNames = null;
            ApplicationCandidateJoinParts::invalidateApplicationsColumnCache();
        }

        if (!$this->applicationsHasColumn('candidate_id')) {
            return;
        }

        $nullCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM applications WHERE candidate_id IS NULL OR candidate_id = 0',
        );
        if ($nullCount === 0) {
            return;
        }

        if ($sm->tablesExist(['profiles'])) {
            $this->connection->executeStatement(
                <<<'SQL'
                UPDATE applications a
                INNER JOIN profiles p ON p.id = a.candidate_profile_id
                SET a.candidate_id = p.user_id
                WHERE a.candidate_id IS NULL OR a.candidate_id = 0
                SQL,
            );
        }

        $this->connection->executeStatement(
            <<<'SQL'
            UPDATE applications a
            INNER JOIN users u ON u.id = a.candidate_profile_id
            SET a.candidate_id = u.id
            WHERE (a.candidate_id IS NULL OR a.candidate_id = 0)
              AND NOT EXISTS (SELECT 1 FROM profiles p WHERE p.id = a.candidate_profile_id)
            SQL,
        );
    }

    /**
     * Filtre « mes candidatures » par {@code users.id} via {@code applications.candidate_id}
     * (remplie à l’insert et par {@see bootstrapCandidateUserLinkIfNeeded}).
     *
     * @return array{0: string, 1: list<mixed>} SQL WHERE (sans AND initial) + paramètres
     */
    private function sqlWhereApplicationBelongsToCandidateUser(int $userId): array
    {
        $this->bootstrapCandidateUserLinkIfNeeded();

        if ($this->applicationsHasColumn('candidate_id')) {
            return ['a.candidate_id = ?', [$userId]];
        }

        $hasProfiles = $this->connection->createSchemaManager()->tablesExist(['profiles']);
        if ($hasProfiles) {
            return ['(p.user_id = ? OR (p.id IS NULL AND a.candidate_profile_id = ?))', [$userId, $userId]];
        }

        return ['a.candidate_profile_id = ?', [$userId]];
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
        $joinCand = ApplicationCandidateJoinParts::joinUsersOnApplicationCandidate($this->connection);
        $appliedTs = $this->sqlAppliedAtExpression('a');
        $hasMatch = $this->applicationsHasColumn('match_percentage');
        $matchSelect = $hasMatch ? 'a.match_percentage AS match_percentage' : 'NULL AS match_percentage';
        $sql = <<<SQL
            SELECT
                a.id AS application_id,
                u.id AS candidate_user_id,
                {$appliedTs} AS applied_at,
                a.status AS status,
                COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—') AS candidate_name,
                u.email AS candidate_email,
                j.id AS job_offer_id,
                j.title AS job_title,
                {$matchSelect}
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            {$joinCand}
            WHERE {$whereEmployer}
            SQL;
        $params = $scopeParams;
        if ($statusEquals !== null && $statusEquals !== '') {
            $sql .= ' AND a.status = ?';
            $params[] = $statusEquals;
        }
        if ($hasMatch) {
            $sql .= ' ORDER BY (a.match_percentage IS NULL) ASC, a.match_percentage DESC, '.$appliedTs.' DESC, a.id DESC';
        } else {
            $sql .= ' ORDER BY '.$appliedTs.' DESC, a.id DESC';
        }

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
        $joinCand = ApplicationCandidateJoinParts::joinUsersOnApplicationCandidate($this->connection);
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT
                u.id AS candidate_user_id,
                COUNT(*) AS application_count,
                MAX(COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—')) AS candidate_name,
                MAX(u.email) AS candidate_email
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            {$joinCand}
            WHERE {$whereEmployer}
            GROUP BY u.id
            ORDER BY application_count DESC, u.id ASC
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
        $joinCand = ApplicationCandidateJoinParts::joinUsersOnApplicationCandidate($this->connection);
        $appliedTs = $this->sqlAppliedAtExpression('a');
        $hasMatch = $this->applicationsHasColumn('match_percentage');
        $matchSel = $hasMatch ? 'a.match_percentage AS match_percentage' : 'NULL AS match_percentage';
        $row = $this->connection->fetchAssociative(
            <<<SQL
            SELECT
                a.id AS application_id,
                {$appliedTs} AS applied_at,
                a.status AS status,
                a.cover_letter AS cover_letter,
                {$matchSel},
                COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—') AS candidate_name,
                u.email AS candidate_email,
                j.id AS job_offer_id,
                j.title AS job_title,
                j.work_type AS job_work_type
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            {$joinCand}
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
            $this->parseNullableFloat($r['match_percentage'] ?? null),
        );
    }

    private function parseNullableFloat(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (\is_float($v) || \is_int($v)) {
            return round((float) $v, 2);
        }
        if (\is_string($v) && is_numeric($v)) {
            return round((float) $v, 2);
        }

        return null;
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
            $this->parseNullableFloat($row['match_percentage'] ?? null),
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
    public function fetchByCandidateUserId(int $candidateUserId): array
    {
        [$whereCand, $whereParams] = $this->sqlWhereApplicationBelongsToCandidateUser($candidateUserId);
        $hasProfiles = $this->connection->createSchemaManager()->tablesExist(['profiles']);
        $joinProfiles = $hasProfiles ? 'LEFT JOIN profiles p ON p.id = a.candidate_profile_id' : '';
        $appliedTs = $this->sqlAppliedAtExpression('a');

        $rows = $this->connection->fetchAllAssociative(
            "SELECT a.* FROM applications a {$joinProfiles} WHERE ({$whereCand}) ORDER BY {$appliedTs} DESC, a.id DESC",
            $whereParams,
        );

        return array_map(fn (array $r) => $this->normalizeRow($r), $rows);
    }

    /**
     * Liste candidat (vue « Mes candidatures ») : filtre par {@code users.id} via {@code profiles.user_id}
     * et/ou {@code applications.candidate_id} (voir {@see sqlWhereApplicationBelongsToCandidateUser}).
     *
     * @return list<array<string, mixed>>
     */
    public function fetchCandidateApplicationsWithJobAndCompany(int $candidateUserId): array
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['applications', 'job_offers'])) {
            return [];
        }

        [$whereCand, $whereParams] = $this->sqlWhereApplicationBelongsToCandidateUser($candidateUserId);
        $hasProfiles = $this->connection->createSchemaManager()->tablesExist(['profiles']);
        $joinProfiles = $hasProfiles ? 'LEFT JOIN profiles p ON p.id = a.candidate_profile_id' : '';
        $appliedTs = $this->sqlAppliedAtExpression('a');

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
            {$joinProfiles}
            WHERE {$whereCand}
            ORDER BY {$appliedTs} DESC, a.id DESC
            SQL,
            $whereParams,
        );

        return array_map(fn (array $r) => $this->normalizeRow($r), $rows);
    }

    /**
     * Suppression par le candidat : uniquement si la ligne lui appartient.
     */
    public function deleteByIdForCandidate(int $applicationId, int $candidateUserId): bool
    {
        [$whereCand, $whereParams] = $this->sqlWhereApplicationBelongsToCandidateUser($candidateUserId);
        $hasProfiles = $this->connection->createSchemaManager()->tablesExist(['profiles']);
        $joinProfiles = $hasProfiles ? 'LEFT JOIN profiles p ON p.id = a.candidate_profile_id' : '';

        return $this->connection->executeStatement(
            "DELETE a FROM applications a {$joinProfiles} WHERE a.id = ? AND ({$whereCand})",
            array_merge([$applicationId], $whereParams),
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
        $appliedTs = $this->sqlAppliedAtExpression('a');
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT a.*
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE {$whereEmployer}
            ORDER BY {$appliedTs} DESC, a.id DESC
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
        $appliedTs = $this->sqlAppliedAtExpression('a');
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT a.*
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE {$whereEmployer}
              AND a.status = 'ACCEPTED'
            ORDER BY {$appliedTs} DESC, a.id DESC
            SQL,
            $params,
        );

        return array_map(fn (array $r) => $this->normalizeRow($r), $rows);
    }

    public function existsForJobOfferAndCandidate(int $jobOfferId, int $candidateUserId): bool
    {
        [$whereCand, $whereParams] = $this->sqlWhereApplicationBelongsToCandidateUser($candidateUserId);
        $hasProfiles = $this->connection->createSchemaManager()->tablesExist(['profiles']);
        $joinProfiles = $hasProfiles ? 'LEFT JOIN profiles p ON p.id = a.candidate_profile_id' : '';

        $v = $this->connection->fetchOne(
            "SELECT a.id FROM applications a {$joinProfiles} WHERE a.job_offer_id = ? AND ({$whereCand}) LIMIT 1",
            array_merge([$jobOfferId], $whereParams),
        );

        return $v !== false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchByJobOfferAndCandidate(int $jobOfferId, int $candidateUserId): ?array
    {
        [$whereCand, $whereParams] = $this->sqlWhereApplicationBelongsToCandidateUser($candidateUserId);
        $hasProfiles = $this->connection->createSchemaManager()->tablesExist(['profiles']);
        $joinProfiles = $hasProfiles ? 'LEFT JOIN profiles p ON p.id = a.candidate_profile_id' : '';

        $row = $this->connection->fetchAssociative(
            "SELECT a.* FROM applications a {$joinProfiles} WHERE a.job_offer_id = ? AND ({$whereCand}) LIMIT 1",
            array_merge([$jobOfferId], $whereParams),
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
        ?float $matchPercentage = null,
    ): int {
        $this->schemaPatcher->ensureGatewayInsertCompatibility(false);
        $this->applicationsColumnNames = null;
        ApplicationCandidateJoinParts::invalidateApplicationsColumnCache();

        $this->bootstrapCandidateUserLinkIfNeeded();
        $this->applicationsColumnNames = null;

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $columns = [];
        $params = [];

        // Fallback robuste: certaines bases legacy ont `applications.id` PRIMARY KEY sans AUTO_INCREMENT.
        // Dans ce cas, MySQL tente souvent d'insérer 0 par défaut -> Duplicate entry '0' for key 'PRIMARY'.
        if ($this->applicationsHasColumn('id') && !$this->isApplicationsIdAutoIncrement()) {
            $columns[] = 'id';
            $params[] = $this->nextApplicationId();
        }

        $columns[] = 'job_offer_id';
        $params[] = $jobOfferId;

        if ($this->applicationsHasColumn('candidate_profile_id')) {
            $columns[] = 'candidate_profile_id';
            $params[] = $candidateProfileId;
        }

        if ($this->applicationsHasColumn('candidate_id')) {
            $columns[] = 'candidate_id';
            $params[] = $candidateId;
        }

        $columns[] = 'status';
        $params[] = $status;

        if ($this->applicationsHasColumn('cv_path')) {
            $columns[] = 'cv_path';
            $params[] = $cvPath;
        }

        $columns[] = 'cover_letter';
        $params[] = $coverLetter;

        if ($this->applicationsHasColumn('applied_at')) {
            $columns[] = 'applied_at';
            $params[] = $now;
        }
        if ($this->applicationsHasColumn('applied_date')) {
            $columns[] = 'applied_date';
            $params[] = $now;
        }
        if (!$this->applicationsHasColumn('applied_at') && !$this->applicationsHasColumn('applied_date')) {
            throw new \RuntimeException(
                'Table applications : colonnes applied_at / applied_date absentes. Exécutez : php bin/console app:recruitment:ensure-applications-schema',
            );
        }

        if ($this->applicationsHasColumn('custom_cv_url')) {
            $columns[] = 'custom_cv_url';
            $params[] = null;
        }

        if ($this->applicationsHasColumn('match_percentage')) {
            $columns[] = 'match_percentage';
            $params[] = $matchPercentage !== null ? round($matchPercentage, 2) : null;
        }

        if ($this->applicationsHasColumn('candidate_score')) {
            $columns[] = 'candidate_score';
            $params[] = null;
        }

        $placeholders = implode(', ', array_fill(0, \count($columns), '?'));
        $sql = 'INSERT INTO applications ('.implode(', ', $columns).') VALUES ('.$placeholders.')';

        $this->connection->executeStatement($sql, $params);

        return $this->resolveInsertedApplicationId($jobOfferId, $candidateId);
    }

    private function isApplicationsIdAutoIncrement(): bool
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof AbstractMySQLPlatform) {
            $row = $this->connection->fetchAssociative(
                <<<'SQL'
                SELECT EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'applications'
                  AND COLUMN_NAME = 'id'
                SQL,
            );
            if (!\is_array($row)) {
                return false;
            }

            return str_contains(strtolower((string) ($row['EXTRA'] ?? '')), 'auto_increment');
        }

        try {
            $col = $this->connection->createSchemaManager()->listTableColumns('applications')['id'] ?? null;

            return $col !== null && method_exists($col, 'getAutoincrement') ? (bool) $col->getAutoincrement() : false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function nextApplicationId(): int
    {
        $v = $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) + 1 FROM applications');
        $n = (int) $v;

        return $n > 0 ? $n : 1;
    }

    /**
     * MySQL sans AUTO_INCREMENT sur {@code id} : {@code lastInsertId()} lève une exception ou vaut 0.
     */
    private function resolveInsertedApplicationId(int $jobOfferId, int $candidateUserId): int
    {
        try {
            $n = (int) $this->connection->lastInsertId();
            if ($n > 0) {
                return $n;
            }
        } catch (\Throwable) {
        }

        if ($this->applicationsHasColumn('candidate_id')) {
            $v = $this->connection->fetchOne(
                'SELECT id FROM applications WHERE job_offer_id = ? AND candidate_id = ? ORDER BY id DESC LIMIT 1',
                [$jobOfferId, $candidateUserId],
            );
            if ($v !== false && (int) $v > 0) {
                return (int) $v;
            }
        }

        $v = $this->connection->fetchOne(
            'SELECT id FROM applications WHERE job_offer_id = ? ORDER BY id DESC LIMIT 1',
            [$jobOfferId],
        );
        if ($v !== false && (int) $v > 0) {
            return (int) $v;
        }

        throw new \RuntimeException(
            'La candidature semble enregistrée mais l’identifiant de ligne est illisible. '
            .'Vérifiez que la colonne applications.id est en AUTO_INCREMENT (php bin/console app:recruitment:ensure-applications-schema).',
        );
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

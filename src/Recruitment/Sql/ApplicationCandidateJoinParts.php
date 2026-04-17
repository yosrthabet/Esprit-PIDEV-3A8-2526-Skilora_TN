<?php

declare(strict_types=1);

namespace App\Recruitment\Sql;

use Doctrine\DBAL\Connection;

/**
 * JOIN {@code users} sur {@code applications} : {@code candidate_id} (users.id), ou {@code profiles.user_id},
 * ou ancien schéma où {@code candidate_profile_id} pointait vers {@code users.id}.
 */
final class ApplicationCandidateJoinParts
{
    private static ?array $applicationsColumnNames = null;

    private static function applicationsHasColumn(Connection $c, string $name): bool
    {
        if (self::$applicationsColumnNames === null) {
            self::$applicationsColumnNames = [];
            if (!$c->createSchemaManager()->tablesExist(['applications'])) {
                return false;
            }
            foreach ($c->createSchemaManager()->listTableColumns('applications') as $col) {
                self::$applicationsColumnNames[strtolower($col->getName())] = true;
            }
        }

        return isset(self::$applicationsColumnNames[strtolower($name)]);
    }

    /**
     * Colonne de tri / affichage : certaines bases n’ont que {@code applied_at}, d’autres que {@code applied_date}.
     */
    public static function sqlCoalesceAppliedTimestamp(Connection $connection, string $tableAlias = 'a'): string
    {
        $hasAt = self::applicationsHasColumn($connection, 'applied_at');
        $hasDate = self::applicationsHasColumn($connection, 'applied_date');
        if ($hasAt && $hasDate) {
            return "COALESCE({$tableAlias}.applied_at, {$tableAlias}.applied_date)";
        }
        if ($hasAt) {
            return "{$tableAlias}.applied_at";
        }
        if ($hasDate) {
            return "{$tableAlias}.applied_date";
        }

        return "{$tableAlias}.id";
    }

    /**
     * À appeler après ALTER TABLE sur {@code applications} (ex. ajout de {@code candidate_id}).
     */
    public static function invalidateApplicationsColumnCache(): void
    {
        self::$applicationsColumnNames = null;
    }

    public static function joinUsersOnApplicationCandidate(Connection $connection): string
    {
        $hasProfiles = $connection->createSchemaManager()->tablesExist(['profiles']);
        $hasCand = self::applicationsHasColumn($connection, 'candidate_id');

        if ($hasProfiles) {
            if ($hasCand) {
                return 'LEFT JOIN profiles pr ON pr.id = a.candidate_profile_id INNER JOIN users u ON u.id = COALESCE(NULLIF(a.candidate_id, 0), pr.user_id, a.candidate_profile_id)';
            }

            return 'INNER JOIN profiles pr ON pr.id = a.candidate_profile_id INNER JOIN users u ON u.id = pr.user_id';
        }

        if ($hasCand) {
            return 'INNER JOIN users u ON u.id = a.candidate_id';
        }

        return 'INNER JOIN users u ON u.id = a.candidate_profile_id';
    }
}

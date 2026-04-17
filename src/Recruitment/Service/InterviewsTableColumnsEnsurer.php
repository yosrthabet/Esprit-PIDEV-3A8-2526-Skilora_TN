<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

/**
 * Aligne la table SQL {@code interviews} sur {@see \App\Recruitment\Entity\JobInterview}
 * (colonnes {@code scheduled_date}, {@code type}, {@code status}, etc.).
 */
final class InterviewsTableColumnsEnsurer
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return list<string> noms des colonnes ajoutées / actions notables
     */
    public function ensureDoctrineColumns(): array
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['interviews'])) {
            return [];
        }

        $this->ensureInterviewsIdAutoIncrementMysql();

        $cols = [];
        foreach ($sm->listTableColumns('interviews') as $c) {
            $cols[strtolower($c->getName())] = true;
        }

        $added = [];
        $p = $this->connection->getDatabasePlatform();
        $sqlite = $p instanceof SQLitePlatform;
        $pgsql = $p instanceof PostgreSQLPlatform;

        if (!isset($cols['interview_format'])) {
            if ($sqlite) {
                $this->connection->executeStatement("ALTER TABLE interviews ADD COLUMN interview_format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            } elseif ($pgsql) {
                $this->connection->executeStatement("ALTER TABLE interviews ADD interview_format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            } else {
                $this->connection->executeStatement("ALTER TABLE interviews ADD interview_format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            }
            $added[] = 'interview_format';
            if (isset($cols['format'])) {
                $this->connection->executeStatement('UPDATE interviews SET interview_format = format WHERE format IS NOT NULL AND format <> \'\'');
            }
            $cols['interview_format'] = true;
        }

        if (!isset($cols['interview_date'])) {
            if ($sqlite) {
                $this->connection->executeStatement('ALTER TABLE interviews ADD COLUMN interview_date DATETIME DEFAULT NULL');
            } elseif ($pgsql) {
                $this->connection->executeStatement('ALTER TABLE interviews ADD interview_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
            } else {
                $this->connection->executeStatement("ALTER TABLE interviews ADD interview_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }
            $added[] = 'interview_date';
            if (isset($cols['scheduled_at'])) {
                $this->connection->executeStatement('UPDATE interviews SET interview_date = scheduled_at WHERE interview_date IS NULL AND scheduled_at IS NOT NULL');
            }
            $cols['interview_date'] = true;
        }

        if (!isset($cols['interview_status'])) {
            if (isset($cols['lifecycle_status'])) {
                if ($sqlite) {
                    $this->connection->executeStatement('ALTER TABLE interviews RENAME COLUMN lifecycle_status TO interview_status');
                } elseif ($pgsql) {
                    $this->connection->executeStatement('ALTER TABLE interviews RENAME COLUMN lifecycle_status TO interview_status');
                } else {
                    $this->connection->executeStatement(
                        "ALTER TABLE interviews CHANGE lifecycle_status interview_status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'",
                    );
                }
                $added[] = 'interview_status (renommé depuis lifecycle_status)';
                $cols['interview_status'] = true;
                unset($cols['lifecycle_status']);
            } else {
                if ($sqlite) {
                    $this->connection->executeStatement("ALTER TABLE interviews ADD COLUMN interview_status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
                } elseif ($pgsql) {
                    $this->connection->executeStatement("ALTER TABLE interviews ADD interview_status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
                } else {
                    $this->connection->executeStatement("ALTER TABLE interviews ADD interview_status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
                }
                $added[] = 'interview_status';
                $cols['interview_status'] = true;
            }
        }

        $this->ensureJobInterviewEntityMirrorColumns($cols, $added);

        return $added;
    }

    /**
     * L’entité Doctrine mappe {@code scheduled_date}, {@code type}, {@code status} — les listes SQL
     * lisaient d’abord {@code interview_date} / {@code interview_format}, souvent vides alors que les données
     * étaient dans les colonnes entité.
     */
    private function ensureJobInterviewEntityMirrorColumns(array &$cols, array &$added): void
    {
        $p = $this->connection->getDatabasePlatform();
        $sqlite = $p instanceof SQLitePlatform;
        $pgsql = $p instanceof PostgreSQLPlatform;

        if (!isset($cols['scheduled_date'])) {
            if ($sqlite) {
                $this->connection->executeStatement('ALTER TABLE interviews ADD COLUMN scheduled_date DATETIME DEFAULT NULL');
            } elseif ($pgsql) {
                $this->connection->executeStatement('ALTER TABLE interviews ADD scheduled_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
            } else {
                $this->connection->executeStatement("ALTER TABLE interviews ADD scheduled_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }
            $added[] = 'scheduled_date';
            $cols['scheduled_date'] = true;
            if (isset($cols['interview_date'])) {
                $this->connection->executeStatement('UPDATE interviews SET scheduled_date = interview_date WHERE scheduled_date IS NULL AND interview_date IS NOT NULL');
            }
            if (isset($cols['scheduled_at'])) {
                $this->connection->executeStatement('UPDATE interviews SET scheduled_date = scheduled_at WHERE scheduled_date IS NULL AND scheduled_at IS NOT NULL');
            }
        } else {
            $coalesce = ['scheduled_date'];
            if (isset($cols['interview_date'])) {
                $coalesce[] = 'interview_date';
            }
            if (isset($cols['scheduled_at'])) {
                $coalesce[] = 'scheduled_at';
            }
            if (\count($coalesce) > 1) {
                $this->connection->executeStatement(
                    'UPDATE interviews SET scheduled_date = COALESCE('.implode(', ', $coalesce).') WHERE scheduled_date IS NULL',
                );
            }
        }

        if (!isset($cols['type'])) {
            if ($sqlite) {
                $this->connection->executeStatement("ALTER TABLE interviews ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            } elseif ($pgsql) {
                $this->connection->executeStatement("ALTER TABLE interviews ADD type VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            } else {
                $this->connection->executeStatement("ALTER TABLE interviews ADD type VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            }
            $added[] = 'type';
            $cols['type'] = true;
            if (isset($cols['interview_format'])) {
                $this->connection->executeStatement(
                    'UPDATE interviews SET type = interview_format WHERE interview_format IS NOT NULL AND TRIM(interview_format) <> \'\'',
                );
            }
            if (isset($cols['format'])) {
                $this->connection->executeStatement(
                    'UPDATE interviews SET type = format WHERE format IS NOT NULL AND TRIM(format) <> \'\' AND (type IS NULL OR type = \'\' OR type = \'ONLINE\')',
                );
            }
        }

        if (!isset($cols['status'])) {
            if ($sqlite) {
                $this->connection->executeStatement("ALTER TABLE interviews ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
            } elseif ($pgsql) {
                $this->connection->executeStatement("ALTER TABLE interviews ADD status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
            } else {
                $this->connection->executeStatement("ALTER TABLE interviews ADD status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
            }
            $added[] = 'status';
            $cols['status'] = true;
            if (isset($cols['interview_status'])) {
                $this->connection->executeStatement(
                    'UPDATE interviews SET status = interview_status WHERE status IS NULL OR status = \'\'',
                );
            }
            if (isset($cols['lifecycle_status'])) {
                $this->connection->executeStatement(
                    'UPDATE interviews SET status = lifecycle_status WHERE (status IS NULL OR status = \'\') AND lifecycle_status IS NOT NULL',
                );
            }
        }

        if (!isset($cols['notes'])) {
            if ($sqlite) {
                $this->connection->executeStatement('ALTER TABLE interviews ADD COLUMN notes CLOB DEFAULT NULL');
            } elseif ($pgsql) {
                $this->connection->executeStatement('ALTER TABLE interviews ADD notes TEXT DEFAULT NULL');
            } else {
                $this->connection->executeStatement('ALTER TABLE interviews ADD notes LONGTEXT DEFAULT NULL');
            }
            $added[] = 'notes';
            $cols['notes'] = true;
        }

        if (!isset($cols['created_date'])) {
            if ($sqlite) {
                $this->connection->executeStatement(
                    'ALTER TABLE interviews ADD COLUMN created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                );
            } elseif ($pgsql) {
                $this->connection->executeStatement(
                    'ALTER TABLE interviews ADD created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP',
                );
            } else {
                $this->connection->executeStatement(
                    "ALTER TABLE interviews ADD created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'",
                );
            }
            $added[] = 'created_date';
            $cols['created_date'] = true;
        }

        $this->backfillLegacyMirrorColumns($cols);
    }

    private function backfillLegacyMirrorColumns(array $cols): void
    {
        if (isset($cols['scheduled_date'], $cols['interview_date'])) {
            $this->connection->executeStatement(
                'UPDATE interviews SET interview_date = scheduled_date WHERE interview_date IS NULL AND scheduled_date IS NOT NULL',
            );
        }
        if (isset($cols['type'], $cols['interview_format'])) {
            $this->connection->executeStatement(
                'UPDATE interviews SET interview_format = type WHERE (interview_format IS NULL OR interview_format = \'\') AND type IS NOT NULL AND type <> \'\'',
            );
        }
        if (isset($cols['status'], $cols['interview_status'])) {
            $this->connection->executeStatement(
                'UPDATE interviews SET interview_status = status WHERE (interview_status IS NULL OR interview_status = \'\') AND status IS NOT NULL AND status <> \'\'',
            );
        }
    }

    private function ensureInterviewsIdAutoIncrementMysql(): void
    {
        $p = $this->connection->getDatabasePlatform();
        if (!$p instanceof AbstractMySQLPlatform) {
            return;
        }
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['interviews'])) {
            return;
        }

        $row = $this->connection->fetchAssociative(
            <<<'SQL'
            SELECT COLUMN_TYPE, EXTRA, COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'interviews'
              AND COLUMN_NAME = 'id'
            SQL,
        );
        if (!\is_array($row)) {
            return;
        }
        if (stripos((string) ($row['EXTRA'] ?? ''), 'auto_increment') !== false) {
            return;
        }

        $colType = trim((string) ($row['COLUMN_TYPE'] ?? 'int(11)'));
        if ($colType === '') {
            $colType = 'int(11)';
        }

        $modifySql = "ALTER TABLE interviews MODIFY id {$colType} NOT NULL AUTO_INCREMENT";
        try {
            $this->connection->executeStatement($modifySql);
        } catch (\Throwable) {
            $key = (string) ($row['COLUMN_KEY'] ?? '');
            if ($key !== 'PRI' && $key !== 'UNI') {
                try {
                    $this->connection->executeStatement('ALTER TABLE interviews ADD PRIMARY KEY (id)');
                    $this->connection->executeStatement($modifySql);
                } catch (\Throwable) {
                }
            }
        }
    }
}

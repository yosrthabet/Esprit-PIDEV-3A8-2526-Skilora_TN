<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

/**
 * Ajoute les colonnes attendues par {@see \App\Recruitment\Entity\JobInterview} si la table existe mais est incomplète
 * (migrations non exécutées ou base importée à la main).
 */
final class InterviewsTableColumnsEnsurer
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return list<string> noms des colonnes ajoutées
     */
    public function ensureDoctrineColumns(): array
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['interviews'])) {
            return [];
        }

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
                // Schéma ancien : une seule colonne lifecycle_status → nom attendu par l’entité JobInterview
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

        return $added;
    }
}

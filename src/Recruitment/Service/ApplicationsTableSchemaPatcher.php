<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

/**
 * Aligne la table SQL `applications` sur {@see \App\Recruitment\Entity\Application}
 * lorsque la table existait déjà sans toutes les colonnes (conflit de nom, install partiel, etc.).
 */
final class ApplicationsTableSchemaPatcher
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function applicationsTableExists(): bool
    {
        return $this->connection->createSchemaManager()->tablesExist(['applications']);
    }

    /**
     * @param bool $truncateIfIncomplete si des lignes existent sans colonnes FK, vide `applications` (et `interviews`) puis continue — à utiliser en dev uniquement
     *
     * @return list<string> noms des colonnes réellement ajoutées
     */
    public function ensureMissingCoreColumns(bool $truncateIfIncomplete = false): array
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['applications'])) {
            return [];
        }

        $platform = $this->connection->getDatabasePlatform();
        $mysql = $platform instanceof AbstractMySQLPlatform;
        $sqlite = $platform instanceof SQLitePlatform;

        $colNames = $this->columnNamesLower('applications');
        $added = [];

        if (!isset($colNames['cv_path'])) {
            $this->connection->executeStatement(
                $sqlite
                    ? "ALTER TABLE applications ADD COLUMN cv_path VARCHAR(500) NOT NULL DEFAULT ''"
                    : "ALTER TABLE applications ADD cv_path VARCHAR(500) NOT NULL DEFAULT ''",
            );
            $added[] = 'cv_path';
        }

        if (!isset($colNames['cover_letter'])) {
            $this->connection->executeStatement(
                $sqlite
                    ? 'ALTER TABLE applications ADD COLUMN cover_letter CLOB DEFAULT NULL'
                    : 'ALTER TABLE applications ADD cover_letter LONGTEXT DEFAULT NULL',
            );
            $added[] = 'cover_letter';
        }

        if (!isset($colNames['status'])) {
            $this->connection->executeStatement(
                $sqlite
                    ? "ALTER TABLE applications ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'IN_PROGRESS'"
                    : "ALTER TABLE applications ADD status VARCHAR(30) NOT NULL DEFAULT 'IN_PROGRESS'",
            );
            $added[] = 'status';
        }

        if (!isset($colNames['applied_at'])) {
            if ($mysql) {
                $this->connection->executeStatement(
                    "ALTER TABLE applications ADD applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'",
                );
            } elseif ($sqlite) {
                $this->connection->executeStatement(
                    'ALTER TABLE applications ADD COLUMN applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                );
            } else {
                $this->connection->executeStatement(
                    'ALTER TABLE applications ADD applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP',
                );
            }
            $added[] = 'applied_at';
        }

        $colNames = $this->columnNamesLower('applications');
        $rowCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM applications');

        $needsFkCols = !isset($colNames['job_offer_id']) || !isset($colNames['candidate_id']);
        if ($needsFkCols) {
            if ($rowCount > 0) {
                if (!$truncateIfIncomplete) {
                    throw new \RuntimeException(
                        'La table applications contient des lignes mais il manque job_offer_id ou candidate_id. '
                        .'Relancez avec --truncate-if-incomplete (efface candidatures et entretiens), ou en SQL : TRUNCATE TABLE applications; '
                        .'puis php bin/console app:recruitment:ensure-applications-schema',
                    );
                }
                $this->truncateApplicationsAndRelated();
                $rowCount = 0;
            }

            if (!isset($colNames['job_offer_id'])) {
                if ($sqlite) {
                    $this->connection->executeStatement('ALTER TABLE applications ADD COLUMN job_offer_id INTEGER NOT NULL');
                } else {
                    $this->connection->executeStatement('ALTER TABLE applications ADD job_offer_id INT NOT NULL');
                }
                $added[] = 'job_offer_id';
            }

            if (!isset($colNames['candidate_id'])) {
                if ($sqlite) {
                    $this->connection->executeStatement('ALTER TABLE applications ADD COLUMN candidate_id INTEGER NOT NULL');
                } else {
                    $this->connection->executeStatement('ALTER TABLE applications ADD candidate_id INT NOT NULL');
                }
                $added[] = 'candidate_id';
            }

            $this->ensureApplicationsIndexesAndForeignKeys();
        }

        return $added;
    }

    private function ensureApplicationsIndexesAndForeignKeys(): void
    {
        $sm = $this->connection->createSchemaManager();

        $idxNames = [];
        foreach ($sm->listTableIndexes('applications') as $idx) {
            $idxNames[strtolower($idx->getName())] = true;
        }

        if (!isset($idxNames['idx_job_app_offer'])) {
            $this->connection->executeStatement('CREATE INDEX IDX_JOB_APP_OFFER ON applications (job_offer_id)');
        }
        if (!isset($idxNames['idx_job_app_candidate'])) {
            $this->connection->executeStatement('CREATE INDEX IDX_JOB_APP_CANDIDATE ON applications (candidate_id)');
        }
        if (!isset($idxNames['uniq_application_candidate'])) {
            $this->connection->executeStatement(
                'CREATE UNIQUE INDEX uniq_application_candidate ON applications (job_offer_id, candidate_id)',
            );
        }

        $fkNames = [];
        foreach ($sm->listTableForeignKeys('applications') as $fk) {
            $fkNames[strtolower($fk->getName())] = true;
        }

        if (!isset($fkNames['fk_job_app_offer'])) {
            try {
                $this->connection->executeStatement(
                    'ALTER TABLE applications ADD CONSTRAINT FK_JOB_APP_OFFER FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE',
                );
            } catch (\Throwable) {
            }
        }

        if (!isset($fkNames['fk_job_app_user'])) {
            try {
                $this->connection->executeStatement(
                    'ALTER TABLE applications ADD CONSTRAINT FK_JOB_APP_USER FOREIGN KEY (candidate_id) REFERENCES users (id) ON DELETE CASCADE',
                );
            } catch (\Throwable) {
            }
        }
    }

    private function truncateApplicationsAndRelated(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $sm = $this->connection->createSchemaManager();

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            if ($sm->tablesExist(['job_interviews'])) {
                $this->connection->executeStatement('TRUNCATE TABLE job_interviews');
            }
            if ($sm->tablesExist(['applications'])) {
                $this->connection->executeStatement('TRUNCATE TABLE applications');
            }
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

            return;
        }

        if ($sm->tablesExist(['interviews'])) {
            $this->connection->executeStatement('DELETE FROM interviews');
        } elseif ($sm->tablesExist(['job_interviews'])) {
            $this->connection->executeStatement('DELETE FROM job_interviews');
        }
        if ($sm->tablesExist(['applications'])) {
            $this->connection->executeStatement('DELETE FROM applications');
        }
    }

    /**
     * @return array<string, true>
     */
    private function columnNamesLower(string $table): array
    {
        $names = [];
        foreach ($this->connection->createSchemaManager()->listTableColumns($table) as $c) {
            $names[strtolower($c->getName())] = true;
        }

        return $names;
    }
}

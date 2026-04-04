<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Si `applications` existait déjà (nom réservé) la migration de renommage a été ignorée :
 * la table peut être incomplète. Ajoute les colonnes manquantes pour {@see \App\Recruitment\Entity\Application}.
 */
final class Version20260405120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'applications: colonnes manquantes (cv_path, cover_letter, status, applied_at)';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            !$this->sm->tablesExist(['applications']),
            'Table applications absente',
        );

        $colNames = [];
        foreach ($this->sm->listTableColumns('applications') as $c) {
            $colNames[strtolower($c->getName())] = true;
        }

        $platform = $this->connection->getDatabasePlatform();
        $sqlite = $platform instanceof SQLitePlatform;

        if (!isset($colNames['cv_path'])) {
            $this->addSql($sqlite
                ? "ALTER TABLE applications ADD COLUMN cv_path VARCHAR(500) NOT NULL DEFAULT ''"
                : "ALTER TABLE applications ADD cv_path VARCHAR(500) NOT NULL DEFAULT ''");
        }

        if (!isset($colNames['cover_letter'])) {
            $this->addSql($sqlite
                ? 'ALTER TABLE applications ADD COLUMN cover_letter CLOB DEFAULT NULL'
                : 'ALTER TABLE applications ADD cover_letter LONGTEXT DEFAULT NULL');
        }

        if (!isset($colNames['status'])) {
            $this->addSql($sqlite
                ? "ALTER TABLE applications ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'IN_PROGRESS'"
                : "ALTER TABLE applications ADD status VARCHAR(30) NOT NULL DEFAULT 'IN_PROGRESS'");
        }

        if (!isset($colNames['applied_at'])) {
            if ($platform instanceof AbstractMySQLPlatform) {
                $this->addSql("ALTER TABLE applications ADD applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");
            } elseif ($sqlite) {
                $this->addSql('ALTER TABLE applications ADD COLUMN applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            } else {
                $this->addSql('ALTER TABLE applications ADD applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}

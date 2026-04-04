<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L’entité {@see \App\Recruitment\Entity\JobInterview} mappe la date sur {@code interview_date}.
 * Les bases créées par les anciennes migrations n’ont que {@code scheduled_at} : on ajoute {@code interview_date} et on recopie.
 */
final class Version20260408120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'interviews: colonne interview_date (Doctrine) + copie depuis scheduled_at si besoin';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            !$this->sm->tablesExist(['interviews']),
            'Table interviews absente',
        );

        $colNames = [];
        foreach ($this->sm->listTableColumns('interviews') as $c) {
            $colNames[strtolower($c->getName())] = true;
        }

        if (isset($colNames['interview_date'])) {
            return;
        }

        $platform = $this->connection->getDatabasePlatform();
        $sqlite = $platform instanceof SQLitePlatform;
        $pgsql = $platform instanceof PostgreSQLPlatform;

        if ($sqlite) {
            $this->addSql('ALTER TABLE interviews ADD COLUMN interview_date DATETIME DEFAULT NULL');
        } elseif ($pgsql) {
            $this->addSql('ALTER TABLE interviews ADD interview_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        } else {
            $this->addSql("ALTER TABLE interviews ADD interview_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        if (isset($colNames['scheduled_at'])) {
            $this->addSql('UPDATE interviews SET interview_date = scheduled_at WHERE interview_date IS NULL AND scheduled_at IS NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
    }
}

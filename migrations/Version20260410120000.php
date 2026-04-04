<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Doctrine mappe le type d’entretien sur {@code format}. Si seule {@code interview_format} existe (migration 09), on ajoute {@code format}.
 */
final class Version20260410120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'interviews: colonne format si absente (copie depuis interview_format si besoin)';
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

        if (isset($colNames['format'])) {
            return;
        }

        $platform = $this->connection->getDatabasePlatform();
        $sqlite = $platform instanceof SQLitePlatform;
        $pgsql = $platform instanceof PostgreSQLPlatform;

        if ($sqlite) {
            $this->addSql("ALTER TABLE interviews ADD COLUMN format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
        } elseif ($pgsql) {
            $this->addSql("ALTER TABLE interviews ADD format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
        } else {
            $this->addSql("ALTER TABLE interviews ADD format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
        }

        if (isset($colNames['interview_format'])) {
            $this->addSql('UPDATE interviews SET format = interview_format WHERE interview_format IS NOT NULL AND interview_format <> \'\'');
        }
    }

    public function down(Schema $schema): void
    {
    }
}

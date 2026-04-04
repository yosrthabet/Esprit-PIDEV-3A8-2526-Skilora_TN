<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L’entité mappe le type d’entretien sur {@code interview_format} (souvent absent : ancienne colonne {@code format}).
 */
final class Version20260409120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'interviews: colonne interview_format + copie depuis format si présente';
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

        if (isset($colNames['interview_format'])) {
            return;
        }

        $platform = $this->connection->getDatabasePlatform();
        $sqlite = $platform instanceof SQLitePlatform;
        $pgsql = $platform instanceof PostgreSQLPlatform;

        if ($sqlite) {
            $this->addSql("ALTER TABLE interviews ADD COLUMN interview_format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
        } elseif ($pgsql) {
            $this->addSql("ALTER TABLE interviews ADD interview_format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
        } else {
            $this->addSql("ALTER TABLE interviews ADD interview_format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
        }

        if (isset($colNames['format'])) {
            $this->addSql('UPDATE interviews SET interview_format = format WHERE format IS NOT NULL AND format <> \'\'');
        }
    }

    public function down(Schema $schema): void
    {
    }
}

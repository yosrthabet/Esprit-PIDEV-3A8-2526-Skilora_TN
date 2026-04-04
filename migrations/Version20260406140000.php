<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds `formations.duration` (VARCHAR) when missing — fixes SQLSTATE[42S22] Unknown column 'f0_.duration'.
 */
final class Version20260406140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add formations.duration column if not present.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['formations'])) {
            return;
        }

        $db = (string) $this->connection->fetchOne('SELECT DATABASE()');
        $exists = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db, 'formations', 'duration']
        );

        if ((int) $exists === 0) {
            $this->addSql('ALTER TABLE formations ADD duration VARCHAR(64) DEFAULT NULL');
            // Prefill from duration_hours if that column exists
            $hasHours = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$db, 'formations', 'duration_hours']
            );
            if (1 === $hasHours) {
                $this->addSql('UPDATE formations SET duration = CONCAT(COALESCE(duration_hours, 0), \' h\') WHERE duration IS NULL OR duration = \'\'');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing duration may lose data.');
    }
}

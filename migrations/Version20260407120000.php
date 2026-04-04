<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Syncs DB with Formation entity: ensures `duration` and `lesson_count` exist.
 *
 * Fixes: SQLSTATE[42S22] Unknown column 'f0_.duration' (and missing lesson_count).
 */
final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add formations.duration and formations.lesson_count when missing.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $sm = $conn->createSchemaManager();

        if (!$sm->tablesExist(['formations'])) {
            $this->addSql('CREATE TABLE formations (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, duration VARCHAR(64) DEFAULT NULL, lesson_count INT NOT NULL, created_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

            return;
        }

        $db = (string) $conn->fetchOne('SELECT DATABASE()');

        $hasColumn = static function ($c, string $database, string $column): bool {
            $n = (int) $c->fetchOne(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$database, 'formations', $column]
            );

            return $n > 0;
        };

        if (!$hasColumn($conn, $db, 'duration')) {
            $this->addSql('ALTER TABLE formations ADD duration VARCHAR(64) DEFAULT NULL');
            if ($hasColumn($conn, $db, 'duration_hours')) {
                $this->addSql('UPDATE formations SET duration = CONCAT(COALESCE(duration_hours, 0), \' h\') WHERE duration IS NULL OR duration = \'\'');
            }
        }

        if (!$hasColumn($conn, $db, 'lesson_count')) {
            if ($hasColumn($conn, $db, 'number_of_lessons')) {
                $this->addSql('ALTER TABLE formations CHANGE number_of_lessons lesson_count INT NOT NULL');
            } else {
                $this->addSql('ALTER TABLE formations ADD lesson_count INT NOT NULL DEFAULT 1');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}

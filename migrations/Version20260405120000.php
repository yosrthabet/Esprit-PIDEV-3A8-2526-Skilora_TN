<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ensures `formations` has exactly the columns the Formation entity needs.
 * Renames legacy `name` → `title` when needed. Safe to run multiple times (idempotent checks).
 */
final class Version20260405120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align formations table: title, description, duration, lesson_count, created_date.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['formations'])) {
            $this->addSql('CREATE TABLE formations (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, duration VARCHAR(64) DEFAULT NULL, lesson_count INT NOT NULL, created_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

            return;
        }

        $db = (string) $this->connection->fetchOne('SELECT DATABASE()');
        $lowerCols = $this->lowerColumnSet($db, 'formations');

        if (isset($lowerCols['name']) && !isset($lowerCols['title'])) {
            $this->addSql('ALTER TABLE formations CHANGE name title VARCHAR(255) NOT NULL');
            $lowerCols = $this->lowerColumnSet($db, 'formations');
        }

        if (!isset($lowerCols['title'])) {
            $this->addSql('ALTER TABLE formations ADD title VARCHAR(255) NOT NULL DEFAULT \'\'');
            $lowerCols['title'] = true;
        }

        if (!isset($lowerCols['description'])) {
            $this->addSql('ALTER TABLE formations ADD description LONGTEXT DEFAULT NULL');
            $this->addSql('UPDATE formations SET description = \'\' WHERE description IS NULL');
            $this->addSql('ALTER TABLE formations MODIFY description LONGTEXT NOT NULL');
            $lowerCols['description'] = true;
        }

        if (!isset($lowerCols['duration'])) {
            $this->addSql('ALTER TABLE formations ADD duration VARCHAR(64) DEFAULT NULL');
            if (isset($lowerCols['duration_hours'])) {
                $this->addSql('UPDATE formations SET duration = CONCAT(COALESCE(duration_hours, 0), \' h\') WHERE duration IS NULL OR duration = \'\'');
            }
            $lowerCols['duration'] = true;
        }

        if (!isset($lowerCols['lesson_count'])) {
            if (isset($lowerCols['number_of_lessons'])) {
                $this->addSql('ALTER TABLE formations CHANGE number_of_lessons lesson_count INT NOT NULL');
            } else {
                $this->addSql('ALTER TABLE formations ADD lesson_count INT NOT NULL DEFAULT 1');
            }
            $lowerCols['lesson_count'] = true;
        }

        if (!isset($lowerCols['created_date'])) {
            if (isset($lowerCols['created_at'])) {
                $this->addSql('ALTER TABLE formations CHANGE created_at created_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            } else {
                $this->addSql('ALTER TABLE formations ADD created_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    /**
     * @return array<string, true>
     */
    private function lowerColumnSet(string $database, string $table): array
    {
        $rows = $this->connection->fetchFirstColumn(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, $table]
        );
        $set = [];
        foreach ($rows as $name) {
            $set[strtolower((string) $name)] = true;
        }

        return $set;
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds `duration` (VARCHAR) when the legacy table only had `duration_hours` (INT).
 * The Formation entity maps: title→name, lesson_count→numberOfLessons, created_date→createdAt.
 */
final class Version20260404180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add duration VARCHAR to formations when missing (legacy schema with duration_hours).';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['formations'])) {
            $this->addSql('CREATE TABLE formations (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, duration VARCHAR(64) DEFAULT NULL, lesson_count INT NOT NULL, created_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

            return;
        }

        $table = $sm->introspectTable('formations');
        $cols = [];
        foreach ($table->getColumns() as $col) {
            $cols[strtolower($col->getName())] = true;
        }

        if (!isset($cols['duration'])) {
            $this->addSql('ALTER TABLE formations ADD duration VARCHAR(64) DEFAULT NULL');
            if (isset($cols['duration_hours'])) {
                $this->addSql('UPDATE formations SET duration = CONCAT(COALESCE(duration_hours, 0), \' h\') WHERE duration IS NULL OR duration = \'\'');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}

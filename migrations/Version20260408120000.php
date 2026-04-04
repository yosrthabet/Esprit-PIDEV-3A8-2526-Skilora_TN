<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds cost, level, category, duration_hours, prerequisites; migrates from duration VARCHAR.
 */
final class Version20260408120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Formation: cost, level, category, duration_hours, prerequisites; drop legacy duration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formations ADD cost NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql("ALTER TABLE formations ADD level VARCHAR(32) NOT NULL DEFAULT 'BEGINNER'");
        $this->addSql("ALTER TABLE formations ADD category VARCHAR(64) NOT NULL DEFAULT 'DEVELOPMENT'");
        $this->addSql('ALTER TABLE formations ADD duration_hours INT DEFAULT NULL');
        $this->addSql('ALTER TABLE formations ADD prerequisites LONGTEXT DEFAULT NULL');

        $this->addSql("UPDATE formations SET duration_hours = 40 WHERE duration IS NULL OR TRIM(duration) = ''");
        $this->addSql("UPDATE formations SET duration_hours = CAST(SUBSTRING_INDEX(TRIM(duration), ' ', 1) AS UNSIGNED) WHERE duration IS NOT NULL AND TRIM(duration) <> '' AND SUBSTRING_INDEX(TRIM(duration), ' ', 1) REGEXP '^[0-9]+$'");
        $this->addSql('UPDATE formations SET duration_hours = 40 WHERE duration_hours IS NULL OR duration_hours < 1');

        $this->addSql('ALTER TABLE formations MODIFY duration_hours INT NOT NULL');
        $this->addSql('ALTER TABLE formations DROP duration');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formations ADD duration VARCHAR(64) DEFAULT NULL');
        $this->addSql("UPDATE formations SET duration = CONCAT(COALESCE(duration_hours, 40), ' h') WHERE duration IS NULL OR duration = ''");
        $this->addSql('ALTER TABLE formations DROP cost');
        $this->addSql('ALTER TABLE formations DROP level');
        $this->addSql('ALTER TABLE formations DROP category');
        $this->addSql('ALTER TABLE formations DROP duration_hours');
        $this->addSql('ALTER TABLE formations DROP prerequisites');
    }
}

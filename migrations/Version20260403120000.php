<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'job_interviews: lieu + lifecycle_status (À venir / Entretien passé)';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            !$this->sm->tablesExist(['job_interviews']),
            'Table job_interviews absente',
        );

        // Colonnes parfois ajoutées à la main : ne pas échouer si déjà présentes.
        if (!$this->columnExists('job_interviews', 'location')) {
            $this->addSql('ALTER TABLE job_interviews ADD location VARCHAR(150) DEFAULT NULL');
        }
        if (!$this->columnExists('job_interviews', 'lifecycle_status')) {
            $this->addSql("ALTER TABLE job_interviews ADD lifecycle_status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->columnExists('job_interviews', 'lifecycle_status')) {
            $this->addSql('ALTER TABLE job_interviews DROP lifecycle_status');
        }
        if ($this->columnExists('job_interviews', 'location')) {
            $this->addSql('ALTER TABLE job_interviews DROP location');
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $n = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );

        return $n > 0;
    }
}

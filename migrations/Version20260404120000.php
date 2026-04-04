<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renomme job_applications → applications (libellé métier : candidatures = applications).
 */
final class Version20260404120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename table job_applications to applications';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->sm->tablesExist(['applications']),
            'Table applications existe déjà',
        );

        $this->skipIf(
            !$this->sm->tablesExist(['job_applications']),
            'Table job_applications absente — rien à renommer',
        );

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->addSql('RENAME TABLE job_applications TO applications');
        } elseif ($platform instanceof SQLitePlatform) {
            $this->addSql('ALTER TABLE job_applications RENAME TO applications');
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE job_applications RENAME TO applications');
        } else {
            $this->addSql('ALTER TABLE job_applications RENAME TO applications');
        }
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(
            $this->sm->tablesExist(['job_applications']),
            'Table job_applications existe déjà',
        );

        $this->skipIf(
            !$this->sm->tablesExist(['applications']),
            'Table applications absente',
        );

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->addSql('RENAME TABLE applications TO job_applications');
        } elseif ($platform instanceof SQLitePlatform) {
            $this->addSql('ALTER TABLE applications RENAME TO job_applications');
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE applications RENAME TO job_applications');
        } else {
            $this->addSql('ALTER TABLE applications RENAME TO job_applications');
        }
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Alignement nom de table métier : {@code interviews} (l’entité Doctrine mappe cette table).
 */
final class Version20260406120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename job_interviews → interviews';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->sm->tablesExist(['interviews']),
            'Table interviews existe déjà — renommage déjà appliqué',
        );
        $this->skipIf(
            !$this->sm->tablesExist(['job_interviews']),
            'Table job_interviews absente — rien à renommer',
        );

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->addSql('RENAME TABLE job_interviews TO interviews');
        } elseif ($platform instanceof SQLitePlatform) {
            $this->addSql('ALTER TABLE job_interviews RENAME TO interviews');
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE job_interviews RENAME TO interviews');
        } else {
            $this->addSql('ALTER TABLE job_interviews RENAME TO interviews');
        }
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(
            $this->sm->tablesExist(['job_interviews']),
            'Table job_interviews existe déjà',
        );
        $this->skipIf(
            !$this->sm->tablesExist(['interviews']),
            'Table interviews absente',
        );

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->addSql('RENAME TABLE interviews TO job_interviews');
        } elseif ($platform instanceof SQLitePlatform) {
            $this->addSql('ALTER TABLE interviews RENAME TO job_interviews');
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE interviews RENAME TO job_interviews');
        } else {
            $this->addSql('ALTER TABLE interviews RENAME TO job_interviews');
        }
    }
}

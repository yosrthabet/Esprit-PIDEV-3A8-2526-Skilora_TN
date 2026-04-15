<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aligns `users` table with App\Entity\User (2FA + terms + password reset columns).
 */
final class Version20260408104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing user columns: two_factor_*, terms_*, reset_token*';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['users'])) {
            return;
        }

        $table = $sm->introspectTable('users');

        if (!$table->hasColumn('two_factor_enabled')) {
            $this->addSql('ALTER TABLE users ADD two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$table->hasColumn('two_factor_enabled_at')) {
            $this->addSql('ALTER TABLE users ADD two_factor_enabled_at DATETIME DEFAULT NULL');
        }
        if (!$table->hasColumn('two_factor_locked_until')) {
            $this->addSql('ALTER TABLE users ADD two_factor_locked_until DATETIME DEFAULT NULL');
        }
        if (!$table->hasColumn('terms_accepted')) {
            $this->addSql('ALTER TABLE users ADD terms_accepted TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$table->hasColumn('terms_accepted_at')) {
            $this->addSql('ALTER TABLE users ADD terms_accepted_at DATETIME DEFAULT NULL');
        }
        if (!$table->hasColumn('reset_token')) {
            $this->addSql('ALTER TABLE users ADD reset_token VARCHAR(64) DEFAULT NULL');
        }
        if (!$table->hasColumn('reset_token_expires_at')) {
            $this->addSql('ALTER TABLE users ADD reset_token_expires_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['users'])) {
            return;
        }

        $table = $sm->introspectTable('users');

        if ($table->hasColumn('reset_token_expires_at')) {
            $this->addSql('ALTER TABLE users DROP reset_token_expires_at');
        }
        if ($table->hasColumn('reset_token')) {
            $this->addSql('ALTER TABLE users DROP reset_token');
        }
        if ($table->hasColumn('terms_accepted_at')) {
            $this->addSql('ALTER TABLE users DROP terms_accepted_at');
        }
        if ($table->hasColumn('terms_accepted')) {
            $this->addSql('ALTER TABLE users DROP terms_accepted');
        }
        if ($table->hasColumn('two_factor_locked_until')) {
            $this->addSql('ALTER TABLE users DROP two_factor_locked_until');
        }
        if ($table->hasColumn('two_factor_enabled_at')) {
            $this->addSql('ALTER TABLE users DROP two_factor_enabled_at');
        }
        if ($table->hasColumn('two_factor_enabled')) {
            $this->addSql('ALTER TABLE users DROP two_factor_enabled');
        }
    }
}

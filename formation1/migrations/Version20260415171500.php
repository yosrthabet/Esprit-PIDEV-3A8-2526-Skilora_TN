<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415171500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing users 2FA columns required by User entity mapping';
    }

    public function up(Schema $schema): void
    {
        // Keep migration idempotent for environments that may already have these columns.
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_locked_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS two_factor_locked_until');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS two_factor_enabled_at');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS two_factor_enabled');
    }
}

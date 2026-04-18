<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415175000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing reset token columns on users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS reset_token_expires_at');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS reset_token');
    }
}

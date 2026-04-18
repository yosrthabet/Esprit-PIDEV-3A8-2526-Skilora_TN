<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415173500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing terms acceptance columns on users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS terms_accepted TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS terms_accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS terms_accepted_at');
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS terms_accepted');
    }
}

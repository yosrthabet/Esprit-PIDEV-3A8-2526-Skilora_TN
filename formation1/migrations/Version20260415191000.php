<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional comment column to formation_reviews';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_reviews ADD COLUMN IF NOT EXISTS comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_reviews DROP COLUMN IF EXISTS comment');
    }
}

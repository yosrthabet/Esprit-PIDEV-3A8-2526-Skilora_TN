<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415201500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add helpful voting counters to formation_reviews';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_reviews ADD COLUMN IF NOT EXISTS useful_count INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE formation_reviews ADD COLUMN IF NOT EXISTS not_useful_count INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_reviews DROP COLUMN IF EXISTS not_useful_count');
        $this->addSql('ALTER TABLE formation_reviews DROP COLUMN IF EXISTS useful_count');
    }
}

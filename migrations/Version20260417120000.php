<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_url column to dm_messages table for photo messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dm_messages ADD image_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dm_messages DROP COLUMN image_url');
    }
}

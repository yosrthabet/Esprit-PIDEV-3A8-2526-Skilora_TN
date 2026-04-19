<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sentiment column to ticket_messages table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket_messages ADD sentiment VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket_messages DROP COLUMN sentiment');
    }
}

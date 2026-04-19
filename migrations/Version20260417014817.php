<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417014817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dm_messages ADD COLUMN is_read BOOLEAN DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE dm_messages ADD COLUMN read_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE dm_messages ADD COLUMN message_type VARCHAR(10) DEFAULT \'text\' NOT NULL');
        $this->addSql('ALTER TABLE dm_messages ADD COLUMN voice_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__dm_messages AS SELECT id, conversation_id, sender_id, body, created_at, updated_at FROM dm_messages');
        $this->addSql('DROP TABLE dm_messages');
        $this->addSql('CREATE TABLE dm_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, conversation_id INTEGER NOT NULL, sender_id INTEGER NOT NULL, body CLOB NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_BC5894CE9AC0396 FOREIGN KEY (conversation_id) REFERENCES dm_conversations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_BC5894CEF624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO dm_messages (id, conversation_id, sender_id, body, created_at, updated_at) SELECT id, conversation_id, sender_id, body, created_at, updated_at FROM __temp__dm_messages');
        $this->addSql('DROP TABLE __temp__dm_messages');
        $this->addSql('CREATE INDEX IDX_BC5894CE9AC0396 ON dm_messages (conversation_id)');
        $this->addSql('CREATE INDEX IDX_BC5894CEF624B39D ON dm_messages (sender_id)');
    }
}

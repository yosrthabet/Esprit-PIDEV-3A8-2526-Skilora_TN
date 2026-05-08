<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508145833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dm_conversations (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(180) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_message_at DATETIME DEFAULT NULL, INDEX idx_dm_conversation_last_message (last_message_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dm_messages (id INT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_BC5894CE9AC0396 (conversation_id), INDEX IDX_BC5894CEF624B39D (sender_id), INDEX idx_dm_message_conversation_id (conversation_id, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dm_participants (id INT AUTO_INCREMENT NOT NULL, unread_count INT NOT NULL, last_read_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F088AC9AC0396 (conversation_id), INDEX IDX_F088ACA76ED395 (user_id), UNIQUE INDEX uniq_dm_participant_user (conversation_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dm_messages ADD CONSTRAINT FK_BC5894CE9AC0396 FOREIGN KEY (conversation_id) REFERENCES dm_conversations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dm_messages ADD CONSTRAINT FK_BC5894CEF624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dm_participants ADD CONSTRAINT FK_F088AC9AC0396 FOREIGN KEY (conversation_id) REFERENCES dm_conversations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dm_participants ADD CONSTRAINT FK_F088ACA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dm_messages DROP FOREIGN KEY FK_BC5894CE9AC0396');
        $this->addSql('ALTER TABLE dm_messages DROP FOREIGN KEY FK_BC5894CEF624B39D');
        $this->addSql('ALTER TABLE dm_participants DROP FOREIGN KEY FK_F088AC9AC0396');
        $this->addSql('ALTER TABLE dm_participants DROP FOREIGN KEY FK_F088ACA76ED395');
        $this->addSql('DROP TABLE dm_conversations');
        $this->addSql('DROP TABLE dm_messages');
        $this->addSql('DROP TABLE dm_participants');
    }
}

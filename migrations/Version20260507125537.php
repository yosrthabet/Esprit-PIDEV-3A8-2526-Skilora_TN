<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507125537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE support_messages (id INT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, internal_note TINYINT NOT NULL, created_at DATETIME NOT NULL, ticket_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_6FB495A9700047D2 (ticket_id), INDEX IDX_6FB495A9F624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_tickets (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(180) NOT NULL, description LONGTEXT NOT NULL, category VARCHAR(30) NOT NULL, priority VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, resolved_at DATETIME DEFAULT NULL, requester_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, INDEX IDX_E9739508ED442CF4 (requester_id), INDEX IDX_E9739508F4BD7827 (assigned_to_id), INDEX idx_support_status_priority (status, priority, updated_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE support_messages ADD CONSTRAINT FK_6FB495A9700047D2 FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_messages ADD CONSTRAINT FK_6FB495A9F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_tickets ADD CONSTRAINT FK_E9739508ED442CF4 FOREIGN KEY (requester_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_tickets ADD CONSTRAINT FK_E9739508F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE support_messages DROP FOREIGN KEY FK_6FB495A9700047D2');
        $this->addSql('ALTER TABLE support_messages DROP FOREIGN KEY FK_6FB495A9F624B39D');
        $this->addSql('ALTER TABLE support_tickets DROP FOREIGN KEY FK_E9739508ED442CF4');
        $this->addSql('ALTER TABLE support_tickets DROP FOREIGN KEY FK_E9739508F4BD7827');
        $this->addSql('DROP TABLE support_messages');
        $this->addSql('DROP TABLE support_tickets');
    }
}

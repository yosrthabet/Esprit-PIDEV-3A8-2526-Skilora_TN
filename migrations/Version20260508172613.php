<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508172613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE community_member_invitations (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, note VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, inviter_id INT NOT NULL, invitee_id INT NOT NULL, INDEX idx_community_member_inviter (inviter_id), INDEX idx_community_member_invitee (invitee_id), INDEX idx_community_member_status_created (status, created_at), INDEX idx_community_member_invitee_status (invitee_id, status), INDEX idx_community_member_inviter_status (inviter_id, status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE community_member_invitations ADD CONSTRAINT FK_B9D8BA58B79F4F04 FOREIGN KEY (inviter_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_member_invitations ADD CONSTRAINT FK_B9D8BA587A512022 FOREIGN KEY (invitee_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE community_member_invitations DROP FOREIGN KEY FK_B9D8BA58B79F4F04');
        $this->addSql('ALTER TABLE community_member_invitations DROP FOREIGN KEY FK_B9D8BA587A512022');
        $this->addSql('DROP TABLE community_member_invitations');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508231149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE formation_review_votes (id INT AUTO_INCREMENT NOT NULL, is_helpful TINYINT NOT NULL, voted_at DATETIME NOT NULL, review_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_535BA0A83E2E969B (review_id), INDEX IDX_535BA0A8A76ED395 (user_id), UNIQUE INDEX uniq_review_vote_user (review_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_reviews (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, communication_rating INT DEFAULT NULL, quality_rating INT DEFAULT NULL, timeliness_rating INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, contract_id INT NOT NULL, reviewer_id INT NOT NULL, reviewed_user_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, INDEX IDX_F39F5C942576E0FD (contract_id), INDEX IDX_F39F5C9470574616 (reviewer_id), INDEX IDX_F39F5C94B9A2A077 (reviewed_user_id), INDEX IDX_F39F5C94DE12AB56 (created_by), INDEX IDX_F39F5C9416FE72E1 (updated_by), UNIQUE INDEX uniq_job_review (contract_id, reviewer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_ticket_attachments (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, stored_path VARCHAR(512) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, file_size INT NOT NULL, uploaded_at DATETIME NOT NULL, ticket_id INT NOT NULL, uploaded_by INT NOT NULL, INDEX IDX_920F0F1D700047D2 (ticket_id), INDEX IDX_920F0F1DE3E73126 (uploaded_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formation_review_votes ADD CONSTRAINT FK_535BA0A83E2E969B FOREIGN KEY (review_id) REFERENCES formation_reviews (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_review_votes ADD CONSTRAINT FK_535BA0A8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_F39F5C942576E0FD FOREIGN KEY (contract_id) REFERENCES finance_contracts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_F39F5C9470574616 FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_F39F5C94B9A2A077 FOREIGN KEY (reviewed_user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_F39F5C94DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_F39F5C9416FE72E1 FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_ticket_attachments ADD CONSTRAINT FK_920F0F1D700047D2 FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_ticket_attachments ADD CONSTRAINT FK_920F0F1DE3E73126 FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_tickets ADD feedback_rating INT DEFAULT NULL, ADD feedback_comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE formation_review_votes DROP FOREIGN KEY FK_535BA0A83E2E969B');
        $this->addSql('ALTER TABLE formation_review_votes DROP FOREIGN KEY FK_535BA0A8A76ED395');
        $this->addSql('ALTER TABLE job_reviews DROP FOREIGN KEY FK_F39F5C942576E0FD');
        $this->addSql('ALTER TABLE job_reviews DROP FOREIGN KEY FK_F39F5C9470574616');
        $this->addSql('ALTER TABLE job_reviews DROP FOREIGN KEY FK_F39F5C94B9A2A077');
        $this->addSql('ALTER TABLE job_reviews DROP FOREIGN KEY FK_F39F5C94DE12AB56');
        $this->addSql('ALTER TABLE job_reviews DROP FOREIGN KEY FK_F39F5C9416FE72E1');
        $this->addSql('ALTER TABLE support_ticket_attachments DROP FOREIGN KEY FK_920F0F1D700047D2');
        $this->addSql('ALTER TABLE support_ticket_attachments DROP FOREIGN KEY FK_920F0F1DE3E73126');
        $this->addSql('DROP TABLE formation_review_votes');
        $this->addSql('DROP TABLE job_reviews');
        $this->addSql('DROP TABLE support_ticket_attachments');
        $this->addSql('ALTER TABLE support_tickets DROP feedback_rating, DROP feedback_comment');
    }
}

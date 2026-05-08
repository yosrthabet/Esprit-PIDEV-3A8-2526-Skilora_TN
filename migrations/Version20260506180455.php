<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506180455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS companies (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(160) NOT NULL, industry VARCHAR(160) DEFAULT NULL, website VARCHAR(180) DEFAULT NULL, location VARCHAR(120) DEFAULT NULL, description LONGTEXT DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_8244AA3A7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS job_offers (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, requirements LONGTEXT DEFAULT NULL, skills_required LONGTEXT DEFAULT NULL, location VARCHAR(140) DEFAULT NULL, work_type VARCHAR(20) DEFAULT NULL, experience_level VARCHAR(20) DEFAULT NULL, min_salary NUMERIC(10, 2) DEFAULT NULL, max_salary NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, company_name VARCHAR(180) DEFAULT NULL, feed_source VARCHAR(32) DEFAULT NULL, feed_source_id VARCHAR(255) DEFAULT NULL, feed_url VARCHAR(700) DEFAULT NULL, source_quality INT NOT NULL, views_count INT NOT NULL, applications_count INT NOT NULL, is_featured TINYINT NOT NULL, posted_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, expires_at DATETIME DEFAULT NULL, company_id INT DEFAULT NULL, INDEX IDX_8A4229A6979B1AD6 (company_id), INDEX idx_job_status_posted (status, posted_at), INDEX idx_job_feed_source (feed_source, feed_source_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS applications (id INT AUTO_INCREMENT NOT NULL, job_offer_id INT NOT NULL, candidate_id INT NOT NULL, cover_letter LONGTEXT DEFAULT NULL, cv_path VARCHAR(500) DEFAULT NULL, match_score INT NOT NULL, match_reasons JSON NOT NULL, status VARCHAR(20) NOT NULL, applied_at DATETIME NOT NULL, INDEX IDX_F7C2B34570F8F019 (job_offer_id), INDEX IDX_F7C2B34591BD19C5 (candidate_id), UNIQUE INDEX uniq_application_candidate_job (job_offer_id, candidate_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS saved_jobs (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, job_offer_id INT NOT NULL, saved_at DATETIME NOT NULL, INDEX IDX_1E223843A76ED395 (user_id), INDEX IDX_1E22384370F8F019 (job_offer_id), UNIQUE INDEX uniq_saved_job_user_offer (user_id, job_offer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS job_preferences (id INT AUTO_INCREMENT NOT NULL, target_title VARCHAR(120) DEFAULT NULL, location VARCHAR(120) DEFAULT NULL, work_type VARCHAR(20) DEFAULT NULL, min_salary INT DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_8A5DBDBCA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT FK_8244AA3A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offers ADD CONSTRAINT FK_8A4229A6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C2B34570F8F019 FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C2B34591BD19C5 FOREIGN KEY (candidate_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE saved_jobs ADD CONSTRAINT FK_1E223843A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE saved_jobs ADD CONSTRAINT FK_1E22384370F8F019 FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_preferences ADD CONSTRAINT FK_8A5DBDBCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A7E3C61F9');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY FK_8A4229A6979B1AD6');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C2B34570F8F019');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C2B34591BD19C5');
        $this->addSql('ALTER TABLE saved_jobs DROP FOREIGN KEY FK_1E223843A76ED395');
        $this->addSql('ALTER TABLE saved_jobs DROP FOREIGN KEY FK_1E22384370F8F019');
        $this->addSql('ALTER TABLE job_preferences DROP FOREIGN KEY FK_8A5DBDBCA76ED395');
        $this->addSql('DROP TABLE applications');
        $this->addSql('DROP TABLE saved_jobs');
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP TABLE job_offers');
        $this->addSql('DROP TABLE job_preferences');
    }
}

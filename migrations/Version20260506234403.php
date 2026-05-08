<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506234403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hire_offers (id INT AUTO_INCREMENT NOT NULL, salary_offered NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) NOT NULL, contract_type VARCHAR(80) DEFAULT NULL, start_date DATE DEFAULT NULL, benefits LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, application_id INT NOT NULL, INDEX IDX_5425907D3E030ACD (application_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_interviews (id INT AUTO_INCREMENT NOT NULL, scheduled_at DATETIME NOT NULL, duration_minutes INT NOT NULL, format VARCHAR(20) NOT NULL, meeting_provider VARCHAR(40) NOT NULL, meeting_url VARCHAR(700) DEFAULT NULL, location VARCHAR(180) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, application_id INT NOT NULL, UNIQUE INDEX UNIQ_5002B69F3E030ACD (application_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hire_offers ADD CONSTRAINT FK_5425907D3E030ACD FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_interviews ADD CONSTRAINT FK_5002B69F3E030ACD FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hire_offers DROP FOREIGN KEY FK_5425907D3E030ACD');
        $this->addSql('ALTER TABLE job_interviews DROP FOREIGN KEY FK_5002B69F3E030ACD');
        $this->addSql('DROP TABLE hire_offers');
        $this->addSql('DROP TABLE job_interviews');
    }
}

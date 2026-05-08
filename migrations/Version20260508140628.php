<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508140628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE formation_certificates (id INT AUTO_INCREMENT NOT NULL, verification_id VARCHAR(64) NOT NULL, issued_at DATETIME NOT NULL, enrollment_id INT NOT NULL, UNIQUE INDEX uniq_certificate_enrollment (enrollment_id), UNIQUE INDEX uniq_certificate_verification_id (verification_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation_enrollments (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, enrolled_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_9325E1E9A76ED395 (user_id), INDEX IDX_9325E1E95200282E (formation_id), UNIQUE INDEX uniq_formation_enrollment_user (formation_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation_lesson_progress (id INT AUTO_INCREMENT NOT NULL, progress_percent INT NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, enrollment_id INT NOT NULL, module_id INT NOT NULL, INDEX IDX_F99813B28F7DB25B (enrollment_id), INDEX IDX_F99813B2AFC2B591 (module_id), UNIQUE INDEX uniq_lesson_progress_enrollment_module (enrollment_id, module_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation_modules (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, position INT NOT NULL, duration_minutes INT NOT NULL, created_at DATETIME NOT NULL, formation_id INT NOT NULL, INDEX IDX_6B4806AC5200282E (formation_id), UNIQUE INDEX uniq_formation_module_position (formation_id, position), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formations (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, category VARCHAR(120) NOT NULL, level VARCHAR(20) NOT NULL, duration_hours INT NOT NULL, price_amount NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, trainer_id INT NOT NULL, INDEX IDX_40902137FB08EDF6 (trainer_id), INDEX idx_formation_status_category (status, category), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formation_certificates ADD CONSTRAINT FK_9C780D238F7DB25B FOREIGN KEY (enrollment_id) REFERENCES formation_enrollments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_enrollments ADD CONSTRAINT FK_9325E1E9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_enrollments ADD CONSTRAINT FK_9325E1E95200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_lesson_progress ADD CONSTRAINT FK_F99813B28F7DB25B FOREIGN KEY (enrollment_id) REFERENCES formation_enrollments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_lesson_progress ADD CONSTRAINT FK_F99813B2AFC2B591 FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_modules ADD CONSTRAINT FK_6B4806AC5200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formations ADD CONSTRAINT FK_40902137FB08EDF6 FOREIGN KEY (trainer_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE formation_certificates DROP FOREIGN KEY FK_9C780D238F7DB25B');
        $this->addSql('ALTER TABLE formation_enrollments DROP FOREIGN KEY FK_9325E1E9A76ED395');
        $this->addSql('ALTER TABLE formation_enrollments DROP FOREIGN KEY FK_9325E1E95200282E');
        $this->addSql('ALTER TABLE formation_lesson_progress DROP FOREIGN KEY FK_F99813B28F7DB25B');
        $this->addSql('ALTER TABLE formation_lesson_progress DROP FOREIGN KEY FK_F99813B2AFC2B591');
        $this->addSql('ALTER TABLE formation_modules DROP FOREIGN KEY FK_6B4806AC5200282E');
        $this->addSql('ALTER TABLE formations DROP FOREIGN KEY FK_40902137FB08EDF6');
        $this->addSql('DROP TABLE formation_certificates');
        $this->addSql('DROP TABLE formation_enrollments');
        $this->addSql('DROP TABLE formation_lesson_progress');
        $this->addSql('DROP TABLE formation_modules');
        $this->addSql('DROP TABLE formations');
    }
}

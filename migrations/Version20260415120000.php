<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enrollment completion fields + certificates table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_enrollments ADD is_completed TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE formation_enrollments ADD completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE certificates (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, formation_id INT NOT NULL, issued_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CERT_USER (user_id), INDEX IDX_CERT_FORMATION (formation_id), UNIQUE INDEX uniq_certificate_user_formation (user_id, formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE certificates ADD CONSTRAINT FK_CERT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE certificates ADD CONSTRAINT FK_CERT_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE certificates');
        $this->addSql('ALTER TABLE formation_enrollments DROP completed_at');
        $this->addSql('ALTER TABLE formation_enrollments DROP is_completed');
    }
}

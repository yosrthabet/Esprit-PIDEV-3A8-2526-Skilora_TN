<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_interviews (planification entretien par candidature acceptée)';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->sm->tablesExist(['job_interviews']),
            'Table job_interviews existe déjà',
        );

        $this->addSql('CREATE TABLE job_interviews (
            id INT AUTO_INCREMENT NOT NULL,
            application_id INT NOT NULL,
            scheduled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            format VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_JOB_INTERVIEW_APPLICATION (application_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE job_interviews ADD CONSTRAINT FK_JOB_INTERVIEW_APPLICATION FOREIGN KEY (application_id) REFERENCES job_applications (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_interviews DROP FOREIGN KEY FK_JOB_INTERVIEW_APPLICATION');
        $this->addSql('DROP TABLE job_interviews');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table des candidatures candidat ↔ offre (CV + lettre optionnelle).
 */
final class Version20260403112845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_applications table';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->sm->tablesExist(['job_applications']) || $this->sm->tablesExist(['applications']),
            'Table des candidatures existe déjà (job_applications ou applications)',
        );

        $this->addSql('CREATE TABLE job_applications (
            id INT AUTO_INCREMENT NOT NULL,
            cv_path VARCHAR(500) NOT NULL,
            cover_letter LONGTEXT DEFAULT NULL,
            status VARCHAR(30) DEFAULT \'IN_PROGRESS\' NOT NULL,
            applied_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            job_offer_id INT NOT NULL,
            candidate_id INT NOT NULL,
            INDEX IDX_JOB_APP_OFFER (job_offer_id),
            INDEX IDX_JOB_APP_CANDIDATE (candidate_id),
            UNIQUE INDEX uniq_job_application_candidate (job_offer_id, candidate_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE job_applications ADD CONSTRAINT FK_JOB_APP_OFFER FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_applications ADD CONSTRAINT FK_JOB_APP_USER FOREIGN KEY (candidate_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications DROP FOREIGN KEY FK_JOB_APP_OFFER');
        $this->addSql('ALTER TABLE job_applications DROP FOREIGN KEY FK_JOB_APP_USER');
        $this->addSql('DROP TABLE job_applications');
    }
}

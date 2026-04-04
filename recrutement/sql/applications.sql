-- Table des candidatures (nom : applications).
-- Fichier : recrutement/sql/applications.sql (module recrutement).
-- Exécuter une fois sur la base si la table manque, ou : php bin/console doctrine:migrations:migrate
-- Ancien nom : job_applications (migration Version20260404120000 renomme si besoin).

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT NOT NULL,
    cv_path VARCHAR(500) NOT NULL,
    cover_letter LONGTEXT DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'IN_PROGRESS' NOT NULL,
    applied_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    job_offer_id INT NOT NULL,
    candidate_id INT NOT NULL,
    INDEX IDX_JOB_APP_OFFER (job_offer_id),
    INDEX IDX_JOB_APP_CANDIDATE (candidate_id),
    UNIQUE INDEX uniq_application_candidate (job_offer_id, candidate_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

ALTER TABLE applications ADD CONSTRAINT FK_JOB_APP_OFFER FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE;
ALTER TABLE applications ADD CONSTRAINT FK_JOB_APP_USER FOREIGN KEY (candidate_id) REFERENCES users (id) ON DELETE CASCADE;

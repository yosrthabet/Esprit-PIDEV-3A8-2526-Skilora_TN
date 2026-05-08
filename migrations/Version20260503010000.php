<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add context fields to dm_conversations and create job_reviews table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dm_conversations ADD context_type VARCHAR(30) DEFAULT NULL, ADD context_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_dm_conv_context ON dm_conversations (context_type, context_id)');

        $this->addSql('CREATE TABLE job_reviews (
            id INT AUTO_INCREMENT NOT NULL,
            contract_id INT NOT NULL,
            author_id INT NOT NULL,
            target_id INT NOT NULL,
            rating SMALLINT NOT NULL,
            comment LONGTEXT DEFAULT NULL,
            review_type VARCHAR(30) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_job_review_author_contract (author_id, contract_id),
            INDEX IDX_JOB_REVIEWS_CONTRACT (contract_id),
            INDEX IDX_JOB_REVIEWS_TARGET (target_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_JR_CONTRACT FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_JR_AUTHOR FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_reviews ADD CONSTRAINT FK_JR_TARGET FOREIGN KEY (target_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE job_reviews');
        $this->addSql('DROP INDEX idx_dm_conv_context ON dm_conversations');
        $this->addSql('ALTER TABLE dm_conversations DROP context_type, DROP context_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Formation module: reviews table for rating analytics (one row per user per formation).
 */
final class Version20260412120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add formation_reviews for formation rating analytics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE formation_reviews (
            id INT AUTO_INCREMENT NOT NULL,
            formation_id INT NOT NULL,
            user_id INT NOT NULL,
            rating SMALLINT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX formation_review_user_unique ON formation_reviews (formation_id, user_id)');
        $this->addSql('CREATE INDEX IDX_formation_reviews_formation ON formation_reviews (formation_id)');
        $this->addSql('ALTER TABLE formation_reviews ADD CONSTRAINT FK_formation_reviews_formation FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_reviews ADD CONSTRAINT FK_formation_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY FK_formation_reviews_formation');
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY FK_formation_reviews_user');
        $this->addSql('DROP TABLE formation_reviews');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415204500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create formation_review_likes table to enforce one vote per user/review';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS formation_review_likes (
            id INT AUTO_INCREMENT NOT NULL,
            review_id INT NOT NULL,
            user_id INT NOT NULL,
            vote SMALLINT NOT NULL,
            UNIQUE INDEX uniq_review_like_user_review (review_id, user_id),
            INDEX IDX_review_likes_review (review_id),
            INDEX IDX_review_likes_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formation_review_likes ADD CONSTRAINT FK_review_likes_review FOREIGN KEY (review_id) REFERENCES formation_reviews (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_review_likes ADD CONSTRAINT FK_review_likes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS formation_review_likes');
    }
}

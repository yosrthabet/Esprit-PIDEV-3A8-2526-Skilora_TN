<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508145236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE community_feed_comments (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, post_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_1FA49EE04B89032C (post_id), INDEX IDX_1FA49EE0F675F31B (author_id), INDEX idx_community_comment_post_created (post_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_feed_likes (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8D8E6BDD4B89032C (post_id), INDEX IDX_8D8E6BDDA76ED395 (user_id), UNIQUE INDEX uniq_community_like_user_post (post_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_feed_posts (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, likes_count INT NOT NULL, comments_count INT NOT NULL, moderation_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, author_id INT NOT NULL, INDEX IDX_4C199F5AF675F31B (author_id), INDEX idx_community_feed_status_created (status, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE community_feed_comments ADD CONSTRAINT FK_1FA49EE04B89032C FOREIGN KEY (post_id) REFERENCES community_feed_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_comments ADD CONSTRAINT FK_1FA49EE0F675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_likes ADD CONSTRAINT FK_8D8E6BDD4B89032C FOREIGN KEY (post_id) REFERENCES community_feed_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_likes ADD CONSTRAINT FK_8D8E6BDDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_posts ADD CONSTRAINT FK_4C199F5AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE community_feed_comments DROP FOREIGN KEY FK_1FA49EE04B89032C');
        $this->addSql('ALTER TABLE community_feed_comments DROP FOREIGN KEY FK_1FA49EE0F675F31B');
        $this->addSql('ALTER TABLE community_feed_likes DROP FOREIGN KEY FK_8D8E6BDD4B89032C');
        $this->addSql('ALTER TABLE community_feed_likes DROP FOREIGN KEY FK_8D8E6BDDA76ED395');
        $this->addSql('ALTER TABLE community_feed_posts DROP FOREIGN KEY FK_4C199F5AF675F31B');
        $this->addSql('DROP TABLE community_feed_comments');
        $this->addSql('DROP TABLE community_feed_likes');
        $this->addSql('DROP TABLE community_feed_posts');
    }
}

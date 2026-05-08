<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508150354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE community_blog_articles (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(220) NOT NULL, excerpt LONGTEXT DEFAULT NULL, content LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, author_id INT NOT NULL, INDEX IDX_AB6F8A22F675F31B (author_id), INDEX idx_community_blog_status_published (status, published_at), UNIQUE INDEX uniq_community_blog_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_spaces_event_rsvps (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_462FF74D71F7E88B (event_id), INDEX IDX_462FF74DA76ED395 (user_id), UNIQUE INDEX uniq_community_event_rsvp (event_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_spaces_events (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, starts_at DATETIME NOT NULL, location VARCHAR(180) DEFAULT NULL, online_url VARCHAR(2048) DEFAULT NULL, rsvps_count INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, host_id INT NOT NULL, INDEX IDX_E49683F51FB8D185 (host_id), INDEX idx_community_event_starts (starts_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_spaces_group_members (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(30) NOT NULL, joined_at DATETIME NOT NULL, group_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_41172F92FE54D947 (group_id), INDEX IDX_41172F92A76ED395 (user_id), UNIQUE INDEX uniq_community_group_member (group_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_spaces_groups (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(160) NOT NULL, description LONGTEXT DEFAULT NULL, members_count INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_477CEDCF7E3C61F9 (owner_id), INDEX idx_community_group_created (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE community_blog_articles ADD CONSTRAINT FK_AB6F8A22F675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_spaces_event_rsvps ADD CONSTRAINT FK_462FF74D71F7E88B FOREIGN KEY (event_id) REFERENCES community_spaces_events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_spaces_event_rsvps ADD CONSTRAINT FK_462FF74DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_spaces_events ADD CONSTRAINT FK_E49683F51FB8D185 FOREIGN KEY (host_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_spaces_group_members ADD CONSTRAINT FK_41172F92FE54D947 FOREIGN KEY (group_id) REFERENCES community_spaces_groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_spaces_group_members ADD CONSTRAINT FK_41172F92A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_spaces_groups ADD CONSTRAINT FK_477CEDCF7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE community_blog_articles DROP FOREIGN KEY FK_AB6F8A22F675F31B');
        $this->addSql('ALTER TABLE community_spaces_event_rsvps DROP FOREIGN KEY FK_462FF74D71F7E88B');
        $this->addSql('ALTER TABLE community_spaces_event_rsvps DROP FOREIGN KEY FK_462FF74DA76ED395');
        $this->addSql('ALTER TABLE community_spaces_events DROP FOREIGN KEY FK_E49683F51FB8D185');
        $this->addSql('ALTER TABLE community_spaces_group_members DROP FOREIGN KEY FK_41172F92FE54D947');
        $this->addSql('ALTER TABLE community_spaces_group_members DROP FOREIGN KEY FK_41172F92A76ED395');
        $this->addSql('ALTER TABLE community_spaces_groups DROP FOREIGN KEY FK_477CEDCF7E3C61F9');
        $this->addSql('DROP TABLE community_blog_articles');
        $this->addSql('DROP TABLE community_spaces_event_rsvps');
        $this->addSql('DROP TABLE community_spaces_events');
        $this->addSql('DROP TABLE community_spaces_group_members');
        $this->addSql('DROP TABLE community_spaces_groups');
    }
}

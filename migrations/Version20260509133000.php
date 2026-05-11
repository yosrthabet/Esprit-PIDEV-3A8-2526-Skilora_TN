<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enhance community posts with visibility, media, reactions, bookmarks, reports, threaded comments, and group privacy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE community_feed_posts ADD group_id INT DEFAULT NULL, ADD visibility VARCHAR(20) DEFAULT \'public\' NOT NULL, ADD media_path VARCHAR(512) DEFAULT NULL, ADD shares_count INT DEFAULT 0 NOT NULL, ADD reports_count INT DEFAULT 0 NOT NULL, ADD pinned_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_community_feed_group ON community_feed_posts (group_id)');
        $this->addSql('ALTER TABLE community_feed_posts ADD CONSTRAINT FK_community_feed_group FOREIGN KEY (group_id) REFERENCES community_spaces_groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_comments ADD parent_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_community_comment_parent ON community_feed_comments (parent_id)');
        $this->addSql('ALTER TABLE community_feed_comments ADD CONSTRAINT FK_community_comment_parent FOREIGN KEY (parent_id) REFERENCES community_feed_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_spaces_groups ADD privacy VARCHAR(20) DEFAULT \'public\' NOT NULL, ADD image_path VARCHAR(512) DEFAULT NULL');
        $this->addSql('CREATE TABLE community_feed_reactions (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_community_reaction_post (post_id), INDEX IDX_community_reaction_user (user_id), UNIQUE INDEX uniq_community_reaction_user_post (post_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_feed_bookmarks (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_community_bookmark_post (post_id), INDEX IDX_community_bookmark_user (user_id), UNIQUE INDEX uniq_community_bookmark_user_post (post_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE community_feed_reports (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, reporter_id INT NOT NULL, reason VARCHAR(120) NOT NULL, details LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_community_report_post (post_id), INDEX IDX_community_report_reporter (reporter_id), UNIQUE INDEX uniq_community_report_user_post (post_id, reporter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE community_feed_reactions ADD CONSTRAINT FK_community_reaction_post FOREIGN KEY (post_id) REFERENCES community_feed_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_reactions ADD CONSTRAINT FK_community_reaction_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_bookmarks ADD CONSTRAINT FK_community_bookmark_post FOREIGN KEY (post_id) REFERENCES community_feed_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_bookmarks ADD CONSTRAINT FK_community_bookmark_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_reports ADD CONSTRAINT FK_community_report_post FOREIGN KEY (post_id) REFERENCES community_feed_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE community_feed_reports ADD CONSTRAINT FK_community_report_reporter FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE community_feed_reports DROP FOREIGN KEY FK_community_report_post');
        $this->addSql('ALTER TABLE community_feed_reports DROP FOREIGN KEY FK_community_report_reporter');
        $this->addSql('ALTER TABLE community_feed_bookmarks DROP FOREIGN KEY FK_community_bookmark_post');
        $this->addSql('ALTER TABLE community_feed_bookmarks DROP FOREIGN KEY FK_community_bookmark_user');
        $this->addSql('ALTER TABLE community_feed_reactions DROP FOREIGN KEY FK_community_reaction_post');
        $this->addSql('ALTER TABLE community_feed_reactions DROP FOREIGN KEY FK_community_reaction_user');
        $this->addSql('DROP TABLE community_feed_reports');
        $this->addSql('DROP TABLE community_feed_bookmarks');
        $this->addSql('DROP TABLE community_feed_reactions');
        $this->addSql('ALTER TABLE community_spaces_groups DROP privacy, DROP image_path');
        $this->addSql('ALTER TABLE community_feed_comments DROP FOREIGN KEY FK_community_comment_parent');
        $this->addSql('DROP INDEX IDX_community_comment_parent ON community_feed_comments');
        $this->addSql('ALTER TABLE community_feed_comments DROP parent_id');
        $this->addSql('ALTER TABLE community_feed_posts DROP FOREIGN KEY FK_community_feed_group');
        $this->addSql('DROP INDEX IDX_community_feed_group ON community_feed_posts');
        $this->addSql('ALTER TABLE community_feed_posts DROP group_id, DROP visibility, DROP media_path, DROP shares_count, DROP reports_count, DROP pinned_at');
    }
}

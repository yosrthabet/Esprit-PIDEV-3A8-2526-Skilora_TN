<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Safe community merge migration:
 * - Adds missing columns to community_posts (post_type, likes_count, comments_count, shares_count)
 * - Creates community_events table if missing
 * - Creates community_notifications table if missing
 */
final class Version20260421100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing community columns and create community_events + community_notifications tables';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        // ── community_posts: add missing columns ──
        $postCols = array_map(
            fn($c) => $c->getName(),
            $sm->listTableColumns('community_posts')
        );

        if (!in_array('post_type', $postCols, true)) {
            $this->addSql("ALTER TABLE community_posts ADD COLUMN post_type VARCHAR(30) DEFAULT 'STATUS' NOT NULL");
        }
        if (!in_array('likes_count', $postCols, true)) {
            $this->addSql("ALTER TABLE community_posts ADD COLUMN likes_count INT DEFAULT 0 NOT NULL");
        }
        if (!in_array('comments_count', $postCols, true)) {
            $this->addSql("ALTER TABLE community_posts ADD COLUMN comments_count INT DEFAULT 0 NOT NULL");
        }
        if (!in_array('shares_count', $postCols, true)) {
            $this->addSql("ALTER TABLE community_posts ADD COLUMN shares_count INT DEFAULT 0 NOT NULL");
        }

        // ── community_events ──
        if (!$sm->tablesExist(['community_events'])) {
            $this->addSql(<<<'SQL'
CREATE TABLE community_events (
    id INT AUTO_INCREMENT NOT NULL,
    organizer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT DEFAULT NULL,
    event_type VARCHAR(30) NOT NULL DEFAULT 'MEETUP',
    location VARCHAR(255) DEFAULT NULL,
    is_online TINYINT(1) DEFAULT 0 NOT NULL,
    online_link VARCHAR(2048) DEFAULT NULL,
    start_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    max_attendees INT DEFAULT 0 NOT NULL,
    current_attendees INT DEFAULT 0 NOT NULL,
    image_url VARCHAR(2048) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'UPCOMING',
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id),
    INDEX IDX_community_event_organizer (organizer_id),
    CONSTRAINT FK_community_event_organizer FOREIGN KEY (organizer_id) REFERENCES users (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        // ── community_notifications ──
        if (!$sm->tablesExist(['community_notifications'])) {
            $this->addSql(<<<'SQL'
CREATE TABLE community_notifications (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'INFO',
    title VARCHAR(255) NOT NULL,
    message LONGTEXT NOT NULL,
    icon VARCHAR(10) DEFAULT '🔔',
    is_read TINYINT(1) DEFAULT 0 NOT NULL,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id),
    INDEX IDX_community_notification_user (user_id),
    CONSTRAINT FK_community_notification_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS community_notifications');
        $this->addSql('DROP TABLE IF EXISTS community_events');
        $this->addSql('ALTER TABLE community_posts DROP COLUMN IF EXISTS post_type');
        $this->addSql('ALTER TABLE community_posts DROP COLUMN IF EXISTS likes_count');
        $this->addSql('ALTER TABLE community_posts DROP COLUMN IF EXISTS comments_count');
        $this->addSql('ALTER TABLE community_posts DROP COLUMN IF EXISTS shares_count');
    }
}

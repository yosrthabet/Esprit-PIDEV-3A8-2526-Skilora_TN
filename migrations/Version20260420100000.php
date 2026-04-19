<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add OAuth + 2FA fields to user, create login_history and user_session tables';
    }

    public function up(Schema $schema): void
    {
        // User table: OAuth + 2FA columns
        $this->addSql('ALTER TABLE users ADD google_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD github_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD totp_secret VARCHAR(255) DEFAULT NULL');

        // Login History
        $this->addSql('CREATE TABLE login_history (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            ip VARCHAR(45) NOT NULL,
            user_agent LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            method VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_37976E36A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE login_history ADD CONSTRAINT FK_37976E36A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // User Sessions
        $this->addSql('CREATE TABLE user_sessions (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            user_agent LONGTEXT NOT NULL,
            last_activity DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_8849CBDE613FECDF (session_id),
            INDEX IDX_8849CBDEA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_sessions ADD CONSTRAINT FK_8849CBDEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE login_history');
        $this->addSql('DROP TABLE user_sessions');
        $this->addSql('ALTER TABLE users DROP google_id, DROP github_id, DROP totp_secret');
    }
}

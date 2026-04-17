<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates `users` when the DB was imported without this table (login requires it).
 */
final class Version20260411153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table if missing';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->createSchemaManager()->tablesExist(['users']),
            'Table users already exists'
        );

        $this->addSql(<<<'SQL'
CREATE TABLE users (
    id INT AUTO_INCREMENT NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    role VARCHAR(50) DEFAULT NULL,
    full_name VARCHAR(255) DEFAULT NULL,
    photo_url LONGTEXT DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0 NOT NULL,
    is_active TINYINT(1) DEFAULT 1 NOT NULL,
    created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    two_factor_enabled TINYINT(1) DEFAULT 0 NOT NULL,
    two_factor_enabled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    two_factor_locked_until DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    terms_accepted TINYINT(1) DEFAULT 0 NOT NULL,
    terms_accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}

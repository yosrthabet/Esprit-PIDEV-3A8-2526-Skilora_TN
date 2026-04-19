<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create passkey_credentials table for WebAuthn / biometric login';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE passkey_credentials (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            credential_id TEXT NOT NULL,
            public_key TEXT NOT NULL,
            sign_count INT DEFAULT 0 NOT NULL,
            name VARCHAR(255) NOT NULL,
            attestation_type VARCHAR(32) DEFAULT \'none\' NOT NULL,
            transports JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PASSKEY_USER (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_PASSKEY_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE passkey_credentials');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 2 — Create wallets table + fix escrow_accounts FK (employment_contracts → contracts).
 *
 * This migration is hand-written because the auto-generated diff contained ~350
 * ALTER statements that would DROP columns from existing tables (bank_accounts,
 * certificates, payslips, interviews, etc.). Those columns exist in the DB but
 * are not yet mapped by Doctrine entities — dropping them would destroy data.
 */
final class Version20260502150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create wallets table and fix escrow_accounts FK from employment_contracts to contracts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS wallets (
                id          INT AUTO_INCREMENT NOT NULL,
                user_id     INT          NOT NULL,
                balance     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                currency    VARCHAR(10)   NOT NULL DEFAULT 'TND',
                created_at  DATETIME      NOT NULL,
                updated_at  DATETIME      DEFAULT NULL,
                UNIQUE INDEX UNIQ_WALLET_USER (user_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);

        $this->addSql('ALTER TABLE wallets ADD CONSTRAINT FK_WALLET_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // Fix escrow_accounts: old FK points to employment_contracts (JavaFX orphan).
        // Re-point it to contracts (Symfony-managed table).
        $this->addSql('ALTER TABLE escrow_accounts DROP FOREIGN KEY `escrow_accounts_ibfk_1`');
        $this->addSql('ALTER TABLE escrow_accounts ADD CONSTRAINT FK_ESCROW_CONTRACT FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallets DROP FOREIGN KEY FK_WALLET_USER');
        $this->addSql('DROP TABLE wallets');

        // Restore old FK to employment_contracts
        $this->addSql('ALTER TABLE escrow_accounts DROP FOREIGN KEY FK_ESCROW_CONTRACT');
        $this->addSql('ALTER TABLE escrow_accounts ADD CONSTRAINT `escrow_accounts_ibfk_1` FOREIGN KEY (contract_id) REFERENCES employment_contracts (id) ON DELETE CASCADE');
    }
}

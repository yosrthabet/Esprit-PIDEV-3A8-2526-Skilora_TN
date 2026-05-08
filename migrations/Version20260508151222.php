<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508151222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE finance_contract_deliveries (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, message LONGTEXT NOT NULL, attachment_url VARCHAR(2048) DEFAULT NULL, created_at DATETIME NOT NULL, contract_id INT NOT NULL, submitted_by_id INT NOT NULL, INDEX IDX_2EBEA42E2576E0FD (contract_id), INDEX IDX_2EBEA42E79F7D87D (submitted_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE finance_contract_disputes (id INT AUTO_INCREMENT NOT NULL, reason VARCHAR(180) NOT NULL, details LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, admin_resolution LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, resolved_at DATETIME DEFAULT NULL, contract_id INT NOT NULL, opened_by_id INT NOT NULL, INDEX IDX_63827F082576E0FD (contract_id), INDEX IDX_63827F08AB159F5 (opened_by_id), INDEX idx_finance_dispute_status_created (status, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE finance_escrow_transactions (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, contract_id INT NOT NULL, INDEX IDX_AA322CC02576E0FD (contract_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE finance_invoices (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(40) NOT NULL, amount NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, issued_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, contract_id INT NOT NULL, issuer_id INT NOT NULL, recipient_id INT NOT NULL, INDEX IDX_BB79DA882576E0FD (contract_id), INDEX IDX_BB79DA88BB9D6FEE (issuer_id), INDEX IDX_BB79DA88E92F8F78 (recipient_id), UNIQUE INDEX uniq_finance_invoice_number (number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE finance_contract_deliveries ADD CONSTRAINT FK_2EBEA42E2576E0FD FOREIGN KEY (contract_id) REFERENCES finance_contracts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_contract_deliveries ADD CONSTRAINT FK_2EBEA42E79F7D87D FOREIGN KEY (submitted_by_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_contract_disputes ADD CONSTRAINT FK_63827F082576E0FD FOREIGN KEY (contract_id) REFERENCES finance_contracts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_contract_disputes ADD CONSTRAINT FK_63827F08AB159F5 FOREIGN KEY (opened_by_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_escrow_transactions ADD CONSTRAINT FK_AA322CC02576E0FD FOREIGN KEY (contract_id) REFERENCES finance_contracts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_invoices ADD CONSTRAINT FK_BB79DA882576E0FD FOREIGN KEY (contract_id) REFERENCES finance_contracts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_invoices ADD CONSTRAINT FK_BB79DA88BB9D6FEE FOREIGN KEY (issuer_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_invoices ADD CONSTRAINT FK_BB79DA88E92F8F78 FOREIGN KEY (recipient_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_contracts ADD submitted_at DATETIME DEFAULT NULL, ADD approved_at DATETIME DEFAULT NULL, ADD released_at DATETIME DEFAULT NULL, ADD disputed_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE finance_contract_deliveries DROP FOREIGN KEY FK_2EBEA42E2576E0FD');
        $this->addSql('ALTER TABLE finance_contract_deliveries DROP FOREIGN KEY FK_2EBEA42E79F7D87D');
        $this->addSql('ALTER TABLE finance_contract_disputes DROP FOREIGN KEY FK_63827F082576E0FD');
        $this->addSql('ALTER TABLE finance_contract_disputes DROP FOREIGN KEY FK_63827F08AB159F5');
        $this->addSql('ALTER TABLE finance_escrow_transactions DROP FOREIGN KEY FK_AA322CC02576E0FD');
        $this->addSql('ALTER TABLE finance_invoices DROP FOREIGN KEY FK_BB79DA882576E0FD');
        $this->addSql('ALTER TABLE finance_invoices DROP FOREIGN KEY FK_BB79DA88BB9D6FEE');
        $this->addSql('ALTER TABLE finance_invoices DROP FOREIGN KEY FK_BB79DA88E92F8F78');
        $this->addSql('DROP TABLE finance_contract_deliveries');
        $this->addSql('DROP TABLE finance_contract_disputes');
        $this->addSql('DROP TABLE finance_escrow_transactions');
        $this->addSql('DROP TABLE finance_invoices');
        $this->addSql('ALTER TABLE finance_contracts DROP submitted_at, DROP approved_at, DROP released_at, DROP disputed_at');
    }
}

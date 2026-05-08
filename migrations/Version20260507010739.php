<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507010739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE finance_contracts (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) NOT NULL, amount NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) NOT NULL, escrow_funded_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, hire_offer_id INT NOT NULL, UNIQUE INDEX UNIQ_6A87935FE4F0180E (hire_offer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE finance_contracts ADD CONSTRAINT FK_6A87935FE4F0180E FOREIGN KEY (hire_offer_id) REFERENCES hire_offers (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE finance_contracts DROP FOREIGN KEY FK_6A87935FE4F0180E');
        $this->addSql('DROP TABLE finance_contracts');
    }
}

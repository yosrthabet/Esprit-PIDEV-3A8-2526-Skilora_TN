<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Version20260502140923 mistakenly created FK on `contracts.company_Name` instead of `company_id`.
 * Doctrine maps {@see \App\Entity\Finance\Contract::$company} to `company_id` → queries fail with
 * "Unknown column 'c0_.company_id'" until the column is renamed.
 */
final class Version20260505094500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename contracts misnamed company column to company_id (align ORM with MySQL)';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        if (!$conn->createSchemaManager()->tablesExist(['contracts'])) {
            return;
        }

        $cols = $conn->fetchFirstColumn(
            <<<'SQL'
            SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts'
              AND COLUMN_NAME IN ('company_id', 'company_Name', 'company_name')
            SQL,
        );

        if (\in_array('company_id', $cols, true)) {
            return;
        }

        $legacy = null;
        foreach ($cols as $c) {
            if (0 === strcasecmp((string) $c, 'company_Name')) {
                $legacy = (string) $c;
                break;
            }
        }

        if ($legacy === null) {
            $this->write('contracts: expected legacy column company_Name / company_name not found — skipping (add company_id manually if needed).');

            return;
        }

        $fks = $conn->fetchFirstColumn(
            <<<'SQL'
            SELECT DISTINCT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts'
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL,
            [$legacy],
        );

        foreach ($fks as $fkName) {
            $safe = str_replace('`', '``', (string) $fkName);
            $this->addSql(\sprintf('ALTER TABLE contracts DROP FOREIGN KEY `%s`', $safe));
        }

        $indexes = $conn->fetchFirstColumn(
            <<<'SQL'
            SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts'
              AND COLUMN_NAME = ?
              AND INDEX_NAME != 'PRIMARY'
            SQL,
            [$legacy],
        );

        foreach ($indexes as $idxName) {
            $safe = str_replace('`', '``', (string) $idxName);
            $this->addSql(\sprintf('DROP INDEX `%s` ON contracts', $safe));
        }

        $legacyEsc = str_replace('`', '``', $legacy);
        $this->addSql(\sprintf('ALTER TABLE contracts CHANGE `%s` company_id INT NOT NULL', $legacyEsc));
        $this->addSql(
            'ALTER TABLE contracts ADD CONSTRAINT FK_950A973979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Reverting company_id → company_Name risks breaking ORM; restore from backup if required.');
    }
}

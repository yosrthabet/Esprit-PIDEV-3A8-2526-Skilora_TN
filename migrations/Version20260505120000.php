<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Follow-up to {@see Version20260505094500}: some DBs still lack `contracts.company_id`
 * (legacy column casing differs, or FK metadata uses another name). Discover the FK column to
 * `companies` or any column whose name lowercases to `company_name`, then rename to `company_id`.
 */
final class Version20260505120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure contracts.company_id exists (discover legacy company FK / company_name column)';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        if (!$conn->createSchemaManager()->tablesExist(['contracts'])) {
            return;
        }

        $hasCompanyId = (bool) $conn->fetchOne(
            <<<'SQL'
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = 'company_id'
            SQL,
        );
        if ($hasCompanyId) {
            return;
        }

        $legacy = $conn->fetchOne(
            <<<'SQL'
            SELECT DISTINCT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts'
              AND REFERENCED_TABLE_NAME = 'companies' AND REFERENCED_COLUMN_NAME = 'id'
            LIMIT 1
            SQL,
        );

        if (!\is_string($legacy) || $legacy === '') {
            $legacy = $conn->fetchOne(
                <<<'SQL'
                SELECT COLUMN_NAME FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts'
                  AND LOWER(COLUMN_NAME) = 'company_name'
                LIMIT 1
                SQL,
            );
        }

        if (!\is_string($legacy) || $legacy === '' || strcasecmp($legacy, 'company_id') === 0) {
            $this->write('contracts: could not resolve a legacy company column to rename — add company_id manually or inspect information_schema.');

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

        $hasFk = (bool) $conn->fetchOne(
            <<<'SQL'
            SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND CONSTRAINT_NAME = 'FK_950A973979B1AD6'
            SQL,
        );
        if (!$hasFk) {
            $this->addSql(
                'ALTER TABLE contracts ADD CONSTRAINT FK_950A973979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)',
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Cannot revert company_id discovery/rename.');
    }
}

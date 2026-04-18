<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Legacy `formation_enrollments` may omit PRIMARY KEY + AUTO_INCREMENT on `id`
 * (same class of bug as formations). Enrollment persist + flush then throws NoIdentityValue → HTTP 500.
 */
final class Version20260418170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure formation_enrollments.id is PRIMARY KEY + AUTO_INCREMENT; add unique (user_id, formation_id) if missing';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['formation_enrollments'])) {
            return;
        }

        if ($this->columnHasAutoIncrement('formation_enrollments', 'id')) {
            $this->ensureUniqueUserFormation();
            return;
        }

        $dupIds = $this->connection->fetchAllAssociative(
            'SELECT id FROM formation_enrollments GROUP BY id HAVING COUNT(*) > 1',
        );
        if (\count($dupIds) > 0) {
            throw new \RuntimeException(
                'Cannot repair formation_enrollments.id: duplicate id values exist.',
            );
        }

        $dupPairs = $this->connection->fetchAllAssociative(
            'SELECT user_id, formation_id FROM formation_enrollments
             GROUP BY user_id, formation_id HAVING COUNT(*) > 1',
        );
        if (\count($dupPairs) > 0) {
            throw new \RuntimeException(
                'Cannot add unique (user_id, formation_id): duplicate pairs exist. Remove duplicates manually.',
            );
        }

        if (!$this->tableHasPrimaryKey('formation_enrollments')) {
            $this->addSql('ALTER TABLE formation_enrollments ADD PRIMARY KEY (id)');
        }

        $this->addSql('ALTER TABLE formation_enrollments MODIFY id INT NOT NULL AUTO_INCREMENT');

        $maxId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) FROM formation_enrollments');
        $next = max(1, $maxId + 1);
        $this->addSql(sprintf('ALTER TABLE formation_enrollments AUTO_INCREMENT = %d', $next));

        $this->ensureUniqueUserFormation();
    }

    public function down(Schema $schema): void
    {
        // Irreversible; no-op.
    }

    private function ensureUniqueUserFormation(): void
    {
        $exists = $this->connection->fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'formation_enrollments'
               AND INDEX_NAME = 'uniq_formation_enrollment_user_formation'
             LIMIT 1",
        );
        // fetchOne returns false when no row — do not treat that as "index exists"
        if (false !== $exists) {
            return;
        }

        $this->addSql(
            'CREATE UNIQUE INDEX uniq_formation_enrollment_user_formation ON formation_enrollments (user_id, formation_id)',
        );
    }

    private function columnHasAutoIncrement(string $table, string $column): bool
    {
        $row = $this->connection->fetchAssociative(
            'SELECT EXTRA FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );

        if (!\is_array($row) || !isset($row['EXTRA'])) {
            return false;
        }

        return str_contains(strtolower((string) $row['EXTRA']), 'auto_increment');
    }

    private function tableHasPrimaryKey(string $table): bool
    {
        $row = $this->connection->fetchAssociative(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, 'PRIMARY KEY'],
        );

        return \is_array($row) && isset($row['CONSTRAINT_NAME']);
    }
}

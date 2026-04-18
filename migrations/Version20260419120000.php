<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Legacy `certificates` imports may omit PRIMARY KEY + AUTO_INCREMENT on `id`.
 * Marking a formation as completed persists a new Certificate → flush → NoIdentityValue → HTTP 500.
 */
final class Version20260419120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure certificates.id is PRIMARY KEY + AUTO_INCREMENT; add uniq (user_id, formation_id) if missing';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['certificates'])) {
            return;
        }

        if ($this->columnHasAutoIncrement('certificates', 'id')) {
            $this->ensureUniqueUserFormationIndex();
            return;
        }

        // Legacy rows with NULL FKs are invalid for the entity and collapse into one GROUP BY bucket → blocks UNIQUE.
        $this->connection->executeStatement('DELETE FROM certificates WHERE user_id IS NULL OR formation_id IS NULL');

        $dupIds = $this->connection->fetchAllAssociative(
            'SELECT id FROM certificates GROUP BY id HAVING COUNT(*) > 1',
        );
        if (\count($dupIds) > 0) {
            throw new \RuntimeException(
                'Cannot repair certificates.id: duplicate id values exist.',
            );
        }

        $dupPairs = $this->connection->fetchAllAssociative(
            'SELECT user_id, formation_id FROM certificates
             GROUP BY user_id, formation_id HAVING COUNT(*) > 1',
        );
        if (\count($dupPairs) > 0) {
            throw new \RuntimeException(
                'Cannot add unique (user_id, formation_id): duplicate pairs exist.',
            );
        }

        if (!$this->tableHasPrimaryKey('certificates')) {
            $this->addSql('ALTER TABLE certificates ADD PRIMARY KEY (id)');
        }

        $this->addSql('ALTER TABLE certificates MODIFY id INT NOT NULL AUTO_INCREMENT');

        $maxId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) FROM certificates');
        $next = max(1, $maxId + 1);
        $this->addSql(sprintf('ALTER TABLE certificates AUTO_INCREMENT = %d', $next));

        $this->ensureUniqueUserFormationIndex();
    }

    public function down(Schema $schema): void
    {
        // no-op
    }

    private function ensureUniqueUserFormationIndex(): void
    {
        $exists = $this->connection->fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'certificates'
               AND INDEX_NAME = 'uniq_certificate_user_formation'
             LIMIT 1",
        );
        if (false !== $exists) {
            return;
        }

        $this->addSql(
            'CREATE UNIQUE INDEX uniq_certificate_user_formation ON certificates (user_id, formation_id)',
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

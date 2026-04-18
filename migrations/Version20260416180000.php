<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Legacy `formations` imports sometimes omit PRIMARY KEY + AUTO_INCREMENT on `id`.
 * Without that, Doctrine cannot read lastInsertId() after INSERT → HTTP 500 on create.
 */
final class Version20260416180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure formations.id is PRIMARY KEY + AUTO_INCREMENT for Doctrine identity mapping';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['formations'])) {
            return;
        }

        if ($this->formationsIdHasAutoIncrement()) {
            return;
        }

        $dupes = $this->connection->fetchAllAssociative(
            'SELECT id FROM formations GROUP BY id HAVING COUNT(*) > 1',
        );
        if (\count($dupes) > 0) {
            throw new \RuntimeException(
                'Cannot repair formations.id: duplicate id values exist. Resolve duplicates manually, then rerun migrations.',
            );
        }

        if (!$this->tableHasPrimaryKey('formations')) {
            $this->addSql('ALTER TABLE formations ADD PRIMARY KEY (id)');
        }

        $this->addSql('ALTER TABLE formations MODIFY id INT NOT NULL AUTO_INCREMENT');

        $maxId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) FROM formations');
        $next = max(1, $maxId + 1);
        $this->addSql(sprintf('ALTER TABLE formations AUTO_INCREMENT = %d', $next));
    }

    public function down(Schema $schema): void
    {
        // Irreversible without losing AUTO_INCREMENT semantics; no-op.
    }

    private function formationsIdHasAutoIncrement(): bool
    {
        $row = $this->connection->fetchAssociative(
            'SELECT EXTRA FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['formations', 'id'],
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

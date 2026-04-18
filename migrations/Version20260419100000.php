<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Version20260418170000 used `null !== fetchOne()` to detect an existing index; DBAL returns false when no row,
 * so the unique index was skipped. Add it idempotently for DBs that already ran that migration.
 */
final class Version20260419100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uniq_formation_enrollment_user_formation if missing (fix fetchOne false vs null)';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['formation_enrollments'])) {
            return;
        }

        $exists = $this->connection->fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'formation_enrollments'
               AND INDEX_NAME = 'uniq_formation_enrollment_user_formation'
             LIMIT 1",
        );
        if (false !== $exists) {
            return;
        }

        $dupPairs = $this->connection->fetchAllAssociative(
            'SELECT user_id, formation_id FROM formation_enrollments
             GROUP BY user_id, formation_id HAVING COUNT(*) > 1',
        );
        if (\count($dupPairs) > 0) {
            throw new \RuntimeException(
                'Cannot add unique (user_id, formation_id): duplicate pairs exist.',
            );
        }

        $this->addSql(
            'CREATE UNIQUE INDEX uniq_formation_enrollment_user_formation ON formation_enrollments (user_id, formation_id)',
        );
    }

    public function down(Schema $schema): void
    {
        // no-op
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Formation module: reviews, review votes, certificate verification UUID, formation certificate filename.
 *
 * FK column types are taken from information_schema so they match existing tables (signed/unsigned INT).
 */
final class Version20260418160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Formation reviews, certificate verification_id, formation certificate_signature_filename';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['formations']) && !$this->tableHasColumn('formations', 'certificate_signature_filename')) {
            $this->addSql('ALTER TABLE formations ADD certificate_signature_filename VARCHAR(255) DEFAULT NULL');
        }

        if ($sm->tablesExist(['certificates']) && !$this->tableHasColumn('certificates', 'verification_id')) {
            $this->addSql('ALTER TABLE certificates ADD verification_id VARCHAR(36) DEFAULT NULL');
            $this->addSql("UPDATE certificates SET verification_id = UUID() WHERE verification_id IS NULL OR verification_id = ''");
            $this->addSql('ALTER TABLE certificates MODIFY verification_id VARCHAR(36) NOT NULL');
            $this->addSql('CREATE UNIQUE INDEX uniq_certificate_verification_id ON certificates (verification_id)');
        }

        $formationIdType = $this->getReferencedColumnMysqlType('formations', 'id');
        $userIdType = $this->getReferencedColumnMysqlType('users', 'id');

        $reviewsTableExisted = $sm->tablesExist(['formation_reviews']);
        $willCreateFormationReviews = !$reviewsTableExisted && null !== $formationIdType && null !== $userIdType;

        if ($willCreateFormationReviews) {
            $this->addSql(sprintf(
                'CREATE TABLE formation_reviews (
                id INT AUTO_INCREMENT NOT NULL,
                formation_id %s NOT NULL,
                user_id %s NOT NULL,
                rating SMALLINT NOT NULL,
                comment LONGTEXT DEFAULT NULL,
                useful_count INT NOT NULL DEFAULT 0,
                not_useful_count INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
                $formationIdType,
                $userIdType,
            ));
            $this->addSql('CREATE UNIQUE INDEX formation_review_user_unique ON formation_reviews (formation_id, user_id)');
            $this->addSql('CREATE INDEX IDX_formation_reviews_formation ON formation_reviews (formation_id)');
            $this->addSql('ALTER TABLE formation_reviews ADD CONSTRAINT FK_formation_reviews_formation FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE formation_reviews ADD CONSTRAINT FK_formation_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        }

        if (!$sm->tablesExist(['formation_review_likes']) && null !== $userIdType && ($reviewsTableExisted || $willCreateFormationReviews)) {
            $this->addSql(sprintf(
                'CREATE TABLE formation_review_likes (
                id INT AUTO_INCREMENT NOT NULL,
                review_id INT NOT NULL,
                user_id %s NOT NULL,
                vote SMALLINT NOT NULL,
                UNIQUE INDEX uniq_review_like_user_review (review_id, user_id),
                INDEX IDX_review_likes_review (review_id),
                INDEX IDX_review_likes_user (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
                $userIdType,
            ));
            $this->addSql('ALTER TABLE formation_review_likes ADD CONSTRAINT FK_review_likes_review FOREIGN KEY (review_id) REFERENCES formation_reviews (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE formation_review_likes ADD CONSTRAINT FK_review_likes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['formation_review_likes'])) {
            $this->addSql('DROP TABLE formation_review_likes');
        }
        if ($sm->tablesExist(['formation_reviews'])) {
            $this->addSql('DROP TABLE formation_reviews');
        }

        if ($sm->tablesExist(['certificates']) && $this->tableHasColumn('certificates', 'verification_id')) {
            $this->addSql('DROP INDEX uniq_certificate_verification_id ON certificates');
            $this->addSql('ALTER TABLE certificates DROP COLUMN verification_id');
        }

        if ($sm->tablesExist(['formations']) && $this->tableHasColumn('formations', 'certificate_signature_filename')) {
            $this->addSql('ALTER TABLE formations DROP COLUMN certificate_signature_filename');
        }
    }

    private function tableHasColumn(string $table, string $columnName): bool
    {
        foreach ($this->connection->createSchemaManager()->listTableColumns($table) as $column) {
            if ($column->getName() === $columnName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns e.g. "int(10) unsigned" or "int(11)" for use after column name in CREATE TABLE.
     */
    private function getReferencedColumnMysqlType(string $table, string $column): ?string
    {
        $row = $this->connection->fetchAssociative(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );

        if (!\is_array($row) || !isset($row['COLUMN_TYPE'])) {
            return null;
        }

        return (string) $row['COLUMN_TYPE'];
    }
}

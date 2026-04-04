<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aligne la table formations avec l’entité : price, duration, lessons_count, created_at ; supprime prerequisites.
 */
final class Version20260410120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Formation: rename columns (price, duration, lessons_count, created_at), drop prerequisites.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $db = (string) $conn->fetchOne('SELECT DATABASE()');
        if ('' === $db) {
            return;
        }

        $tableExists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db, 'formations']
        ) > 0;

        if (!$tableExists) {
            return;
        }

        $cols = $this->columnNames($conn, $db, 'formations');

        if (\in_array('prerequisites', $cols, true)) {
            $this->addSql('ALTER TABLE formations DROP prerequisites');
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('cost', $cols, true) && !\in_array('price', $cols, true)) {
            $this->addSql('ALTER TABLE formations CHANGE cost price DOUBLE PRECISION DEFAULT NULL');
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('duration_hours', $cols, true) && !\in_array('duration', $cols, true)) {
            $this->addSql('ALTER TABLE formations CHANGE duration_hours duration INT NOT NULL');
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('lesson_count', $cols, true) && !\in_array('lessons_count', $cols, true)) {
            $this->addSql('ALTER TABLE formations CHANGE lesson_count lessons_count INT NOT NULL');
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('created_date', $cols, true) && !\in_array('created_at', $cols, true)) {
            $this->addSql("ALTER TABLE formations CHANGE created_date created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('created_at', $cols, true)) {
            $this->addSql('UPDATE formations SET created_at = UTC_TIMESTAMP() WHERE created_at IS NULL');
            $this->addSql("ALTER TABLE formations MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;
        $db = (string) $conn->fetchOne('SELECT DATABASE()');
        if ('' === $db) {
            return;
        }

        $tableExists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db, 'formations']
        ) > 0;

        if (!$tableExists) {
            return;
        }

        $cols = $this->columnNames($conn, $db, 'formations');

        if (\in_array('price', $cols, true) && !\in_array('cost', $cols, true)) {
            $this->addSql('ALTER TABLE formations CHANGE price cost NUMERIC(12, 2) DEFAULT NULL');
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('duration', $cols, true) && !\in_array('duration_hours', $cols, true)) {
            $this->addSql('ALTER TABLE formations CHANGE duration duration_hours INT NOT NULL');
        }

        if (\in_array('lessons_count', $cols, true) && !\in_array('lesson_count', $cols, true)) {
            $this->addSql('ALTER TABLE formations CHANGE lessons_count lesson_count INT NOT NULL');
        }

        if (\in_array('created_at', $cols, true) && !\in_array('created_date', $cols, true)) {
            $this->addSql("ALTER TABLE formations CHANGE created_at created_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        $this->addSql('ALTER TABLE formations ADD prerequisites LONGTEXT DEFAULT NULL');
    }

    /**
     * @return list<string>
     */
    private function columnNames(Connection $conn, string $database, string $table): array
    {
        /** @var list<string> $cols */
        $cols = $conn->fetchFirstColumn(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$database, $table]
        );

        return $cols;
    }
}

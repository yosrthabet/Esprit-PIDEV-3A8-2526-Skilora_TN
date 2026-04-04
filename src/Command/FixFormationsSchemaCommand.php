<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Réparation manuelle du schéma `formations` et création de `formation_enrollments` si les migrations ne peuvent pas être jouées.
 */
#[AsCommand(
    name: 'app:fix-formations-schema',
    description: 'Aligne `formations` et crée `formation_enrollments` (inscriptions) si absent',
)]
final class FixFormationsSchemaCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->connection;
        $db = (string) $conn->fetchOne('SELECT DATABASE()');

        if ('' === $db) {
            $io->error('Base de données non sélectionnée.');

            return Command::FAILURE;
        }

        $tableExists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db, 'formations']
        ) > 0;

        if (!$tableExists) {
            $io->error('La table `formations` n’existe pas. Exécutez les migrations ou importez une base complète.');

            return Command::FAILURE;
        }

        $changed = false;

        if ($this->hasColumn($conn, $db, 'formations', 'name') && !$this->hasColumn($conn, $db, 'formations', 'title')) {
            $conn->executeStatement('ALTER TABLE formations CHANGE name title VARCHAR(255) NOT NULL');
            $io->writeln('Colonne <info>name</info> → <info>title</info>.');
            $changed = true;
        }

        $cols = $this->columnNames($conn, $db, 'formations');

        if (\in_array('prerequisites', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formations DROP prerequisites');
            $io->writeln('Colonne <info>prerequisites</info> supprimée.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('cost', $cols, true) && !\in_array('price', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formations CHANGE cost price DOUBLE PRECISION DEFAULT NULL');
            $io->writeln('Colonne <info>cost</info> → <info>price</info>.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (!\in_array('price', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formations ADD price DOUBLE PRECISION DEFAULT NULL');
            $io->writeln('Colonne <info>price</info> ajoutée.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('duration_hours', $cols, true) && !\in_array('duration', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formations CHANGE duration_hours duration INT NOT NULL');
            $io->writeln('Colonne <info>duration_hours</info> → <info>duration</info>.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (!\in_array('duration', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formations ADD duration INT NOT NULL DEFAULT 1');
            $io->writeln('Colonne <info>duration</info> ajoutée.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('lesson_count', $cols, true) && !\in_array('lessons_count', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formations CHANGE lesson_count lessons_count INT NOT NULL');
            $io->writeln('Colonne <info>lesson_count</info> → <info>lessons_count</info>.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (!\in_array('lessons_count', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formations ADD lessons_count INT NOT NULL DEFAULT 1');
            $io->writeln('Colonne <info>lessons_count</info> ajoutée.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (!$this->hasColumn($conn, $db, 'formations', 'level')) {
            $conn->executeStatement("ALTER TABLE formations ADD level VARCHAR(32) NOT NULL DEFAULT 'BEGINNER'");
            $io->writeln('Colonne <info>level</info> ajoutée.');
            $changed = true;
        }

        if (!$this->hasColumn($conn, $db, 'formations', 'category')) {
            $conn->executeStatement("ALTER TABLE formations ADD category VARCHAR(64) NOT NULL DEFAULT 'DEVELOPMENT'");
            $io->writeln('Colonne <info>category</info> ajoutée.');
            $changed = true;
        }

        $cols = $this->columnNames($conn, $db, 'formations');
        if (\in_array('created_date', $cols, true) && !\in_array('created_at', $cols, true)) {
            $conn->executeStatement("ALTER TABLE formations CHANGE created_date created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            $io->writeln('Colonne <info>created_date</info> → <info>created_at</info>.');
            $changed = true;
            $cols = $this->columnNames($conn, $db, 'formations');
        }

        if (\in_array('created_at', $cols, true)) {
            $conn->executeStatement('UPDATE formations SET created_at = UTC_TIMESTAMP() WHERE created_at IS NULL');
            $conn->executeStatement("ALTER TABLE formations MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
            $io->writeln('Colonne <info>created_at</info> renseignée et NOT NULL.');
            $changed = true;
        } elseif (!$this->hasColumn($conn, $db, 'formations', 'created_at')) {
            $conn->executeStatement("ALTER TABLE formations ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
            $io->writeln('Colonne <info>created_at</info> ajoutée.');
            $changed = true;
        }

        $enrollmentChanged = $this->ensureFormationEnrollmentsTable($conn, $db, $io);
        $enrollmentColsChanged = $this->ensureEnrollmentCompletionColumns($conn, $db, $io);
        $certChanged = $this->ensureCertificatesTable($conn, $db, $io);
        $certAligned = $this->alignCertificatesTableForFormationEntity($conn, $db, $io);

        if ($changed || $enrollmentChanged || $enrollmentColsChanged || $certChanged || $certAligned) {
            $io->success('Schéma aligné (formations et/ou inscriptions).');
        } else {
            $io->success('Rien à modifier : le schéma semble déjà à jour.');
        }

        return Command::SUCCESS;
    }

    /**
     * Crée la table `formation_enrollments` si elle n’existe pas (évite l’erreur 1146 quand les migrations ne sont pas jouées).
     */
    private function ensureFormationEnrollmentsTable(Connection $conn, string $database, SymfonyStyle $io): bool
    {
        $exists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, 'formation_enrollments']
        ) > 0;

        if ($exists) {
            return false;
        }

        $conn->executeStatement('CREATE TABLE formation_enrollments (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, formation_id INT NOT NULL, enrolled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FE_USER (user_id), INDEX IDX_FE_FORMATION (formation_id), UNIQUE INDEX uniq_user_formation (user_id, formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $conn->executeStatement('ALTER TABLE formation_enrollments ADD CONSTRAINT FK_FE_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE formation_enrollments ADD CONSTRAINT FK_FE_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
        $io->writeln('Table <info>formation_enrollments</info> créée (inscriptions aux formations).');

        return true;
    }

    private function ensureEnrollmentCompletionColumns(Connection $conn, string $database, SymfonyStyle $io): bool
    {
        $exists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, 'formation_enrollments']
        ) > 0;

        if (!$exists) {
            return false;
        }

        $cols = $this->columnNames($conn, $database, 'formation_enrollments');
        $changed = false;

        if (!\in_array('is_completed', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formation_enrollments ADD is_completed TINYINT(1) NOT NULL DEFAULT 0');
            $io->writeln('Colonne <info>is_completed</info> ajoutée sur <info>formation_enrollments</info>.');
            $changed = true;
        }

        $cols = $this->columnNames($conn, $database, 'formation_enrollments');
        if (!\in_array('completed_at', $cols, true)) {
            $conn->executeStatement('ALTER TABLE formation_enrollments ADD completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $io->writeln('Colonne <info>completed_at</info> ajoutée sur <info>formation_enrollments</info>.');
            $changed = true;
        }

        return $changed;
    }

    private function ensureCertificatesTable(Connection $conn, string $database, SymfonyStyle $io): bool
    {
        $exists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, 'certificates']
        ) > 0;

        if ($exists) {
            return false;
        }

        $conn->executeStatement('CREATE TABLE certificates (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, formation_id INT NOT NULL, issued_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CERT_USER (user_id), INDEX IDX_CERT_FORMATION (formation_id), UNIQUE INDEX uniq_certificate_user_formation (user_id, formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $conn->executeStatement('ALTER TABLE certificates ADD CONSTRAINT FK_CERT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $conn->executeStatement('ALTER TABLE certificates ADD CONSTRAINT FK_CERT_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
        $io->writeln('Table <info>certificates</info> créée.');

        return true;
    }

    /**
     * Ancienne base : table certificates liée à trainings.training_id.
     * Entité Symfony : formation_id → formations.id uniquement.
     */
    private function alignCertificatesTableForFormationEntity(Connection $conn, string $database, SymfonyStyle $io): bool
    {
        $exists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, 'certificates']
        ) > 0;

        if (!$exists) {
            return false;
        }

        $cols = $this->columnNames($conn, $database, 'certificates');
        $hasLegacyTraining = \in_array('training_id', $cols, true);
        $hasFormation = \in_array('formation_id', $cols, true);
        $hasIssuedAt = \in_array('issued_at', $cols, true);
        $hasUser = \in_array('user_id', $cols, true);

        $needsAlign = $hasLegacyTraining
            || !$hasFormation
            || !$hasIssuedAt
            || !$hasUser;

        if (!$needsAlign && $hasFormation) {
            $ref = $conn->fetchOne(
                'SELECT REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$database, 'certificates', 'formation_id']
            );
            if (\is_string($ref) && 'formations' !== $ref) {
                $needsAlign = true;
            }
        }

        if (!$needsAlign && $hasFormation && $hasUser) {
            if (!$this->tableHasForeignKeyToTable($conn, $database, 'certificates', 'formations')
                || !$this->tableHasForeignKeyToTable($conn, $database, 'certificates', 'users')) {
                $needsAlign = true;
            }
        }

        if (!$needsAlign) {
            return false;
        }

        $io->writeln('Alignement de <info>certificates</info> sur le modèle Formation (suppression du lien <comment>trainings</comment>)…');

        $this->dropAllForeignKeysOnTable($conn, $database, 'certificates');

        $rowCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM certificates');
        $mustClearForSchema = $hasLegacyTraining || !$hasFormation;
        if ($rowCount > 0 && $mustClearForSchema) {
            $conn->executeStatement('DELETE FROM certificates');
            $io->writeln('  <comment>Lignes certificates supprimées (passage training → formation).</comment>');
        }

        $this->dropNonPrimaryIndexesOnCertificates($conn, $database);

        $cols = $this->columnNames($conn, $database, 'certificates');
        $legacyCols = ['training_id', 'certificate_number', 'verification_token', 'completed_at', 'pdf_path'];
        foreach ($legacyCols as $legacy) {
            if (\in_array($legacy, $cols, true)) {
                $conn->executeStatement('ALTER TABLE certificates DROP COLUMN `'.$legacy.'`');
                $io->writeln(sprintf('  Colonne obsolète <info>%s</info> supprimée.', $legacy));
            }
        }

        $cols = $this->columnNames($conn, $database, 'certificates');
        if (!\in_array('formation_id', $cols, true)) {
            $conn->executeStatement('ALTER TABLE certificates ADD formation_id INT NOT NULL');
            $io->writeln('  Colonne <info>formation_id</info> ajoutée.');
        }

        $cols = $this->columnNames($conn, $database, 'certificates');
        if (!\in_array('issued_at', $cols, true)) {
            $conn->executeStatement('ALTER TABLE certificates ADD issued_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $io->writeln('  Colonne <info>issued_at</info> ajoutée.');
        }

        $hasUserFk = $this->tableHasForeignKeyToTable($conn, $database, 'certificates', 'users');
        if (!$hasUserFk) {
            $conn->executeStatement('ALTER TABLE certificates ADD CONSTRAINT FK_CERT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
            $io->writeln('  Contrainte FK <info>user_id → users</info>.');
        }

        $hasFormationFk = $this->tableHasForeignKeyToTable($conn, $database, 'certificates', 'formations');
        if (!$hasFormationFk) {
            $conn->executeStatement('ALTER TABLE certificates ADD CONSTRAINT FK_CERT_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
            $io->writeln('  Contrainte FK <info>formation_id → formations</info>.');
        }

        $indexes = $conn->fetchFirstColumn(
            'SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME NOT IN (\'PRIMARY\')',
            [$database, 'certificates']
        );
        $hasUniq = false;
        foreach ($indexes as $idx) {
            if ('uniq_certificate_user_formation' === $idx) {
                $hasUniq = true;
            }
        }
        if (!$hasUniq) {
            try {
                $conn->executeStatement('CREATE UNIQUE INDEX uniq_certificate_user_formation ON certificates (user_id, formation_id)');
                $io->writeln('  Index unique <info>(user_id, formation_id)</info>.');
            } catch (\Throwable $e) {
                $io->warning('Index unique certificates : '.$e->getMessage());
            }
        }

        return true;
    }

    private function dropAllForeignKeysOnTable(Connection $conn, string $database, string $table): void
    {
        /** @var list<array{CONSTRAINT_NAME: string}> $rows */
        $rows = $conn->fetchAllAssociative(
            'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, 'FOREIGN KEY']
        );
        foreach ($rows as $row) {
            $name = $row['CONSTRAINT_NAME'];
            $conn->executeStatement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, str_replace('`', '``', $name)));
        }
    }

    /**
     * Supprime les index non-PK avant DROP COLUMN (sinon erreur MySQL sur colonnes type training_id).
     */
    private function dropNonPrimaryIndexesOnCertificates(Connection $conn, string $database): void
    {
        $names = $conn->fetchFirstColumn(
            'SELECT DISTINCT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME <> \'PRIMARY\'',
            [$database, 'certificates']
        );
        foreach ($names as $idx) {
            if (!\is_string($idx) || '' === $idx) {
                continue;
            }
            try {
                $conn->executeStatement(sprintf('ALTER TABLE certificates DROP INDEX `%s`', str_replace('`', '``', $idx)));
            } catch (\Throwable) {
            }
        }
    }

    private function tableHasForeignKeyToTable(Connection $conn, string $database, string $table, string $referencedTable): bool
    {
        $n = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ?',
            [$database, $table, $database, $referencedTable]
        );

        return $n > 0;
    }

    private function hasColumn(Connection $conn, string $database, string $table, string $column): bool
    {
        return (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$database, $table, $column]
        ) > 0;
    }

    /**
     * @return list<string>
     */
    private function columnNames(Connection $conn, string $database, string $table): array
    {
        /** @var list<string> */
        return $conn->fetchFirstColumn(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$database, $table]
        );
    }
}

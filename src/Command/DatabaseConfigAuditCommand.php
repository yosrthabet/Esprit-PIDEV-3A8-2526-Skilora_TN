<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:db:config:audit', description: 'Audit database timezone, SQL mode, buffer pool and collation settings.')]
final class DatabaseConfigAuditCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $phpTimezone = date_default_timezone_get();
        $db = $this->connection->fetchAssociative("SELECT DATABASE() AS db_name, @@global.time_zone AS global_tz, @@session.time_zone AS session_tz, @@system_time_zone AS system_tz, @@sql_mode AS sql_mode, @@innodb_buffer_pool_size AS buffer_pool, @@innodb_flush_log_at_trx_commit AS flush_commit, (SELECT COUNT(*) FROM mysql.time_zone_name) AS tz_tables");
        $db = is_array($db) ? $db : [];
        $collations = $this->connection->fetchAllAssociative("SELECT TABLE_COLLATION, COUNT(*) AS table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() GROUP BY TABLE_COLLATION ORDER BY table_count DESC");

        $io->title('Database configuration audit');
        $io->definitionList(
            ['PHP timezone' => $phpTimezone],
            ['MySQL global timezone' => $this->stringValue($db['global_tz'] ?? null)],
            ['MySQL session timezone' => $this->stringValue($db['session_tz'] ?? null)],
            ['MySQL system timezone' => $this->stringValue($db['system_tz'] ?? null)],
            ['SQL mode' => $this->stringValue($db['sql_mode'] ?? null)],
            ['InnoDB buffer pool size' => $this->formatBytes($this->intValue($db['buffer_pool'] ?? null))],
            ['innodb_flush_log_at_trx_commit' => $this->stringValue($db['flush_commit'] ?? null)],
            ['Timezone table rows' => $this->stringValue($db['tz_tables'] ?? null)],
        );

        $io->section('Table collations');
        $io->table(['Collation', 'Tables'], array_map(static fn (array $row): array => [$row['TABLE_COLLATION'], $row['table_count']], $collations));

        $issues = [];

        if (($db['session_tz'] ?? null) !== '+00:00') {
            $issues[] = 'Session timezone is not forced to UTC.';
        }

        $sqlMode = $this->stringValue($db['sql_mode'] ?? null);
        foreach (['NO_ZERO_DATE', 'NO_ZERO_IN_DATE'] as $requiredMode) {
            if (!str_contains($sqlMode, $requiredMode)) {
                $issues[] = sprintf('Missing SQL mode: %s.', $requiredMode);
            }
        }

        if ($this->intValue($db['buffer_pool'] ?? null) < 134217728) {
            $issues[] = 'innodb_buffer_pool_size is below 128MB.';
        }

        if ($this->intValue($db['tz_tables'] ?? null) === 0) {
            $issues[] = 'MySQL timezone tables are empty.';
        }

        if ($this->stringValue($db['global_tz'] ?? null) === 'SYSTEM') {
            $issues[] = 'MySQL global timezone uses SYSTEM instead of an explicit value.';
        }

        if ($this->stringValue($db['flush_commit'] ?? null) === '1') {
            $issues[] = 'innodb_flush_log_at_trx_commit=1 is safe but slow for development.';
        }

        if ($issues === []) {
            $io->success('No actionable database config issues detected.');
            return Command::SUCCESS;
        }

        $io->warning('Detected issues:');
        $io->listing($issues);

        $io->section('Suggested server-level MariaDB/MySQL settings');
        $io->writeln([
            '[mysqld]',
            'default_time_zone = +00:00',
            'innodb_buffer_pool_size = 128M   ; dev minimum, raise if RAM allows',
            'sql_mode = STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE',
            'innodb_flush_log_at_trx_commit = 2   ; dev only, keep 1 in production',
            '',
            '# Load timezone tables once on the server:',
            '# mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p mysql',
        ]);

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return sprintf('%.2f %s', $value, $units[$power]);
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}

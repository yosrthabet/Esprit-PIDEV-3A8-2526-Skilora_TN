<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:db:session:fix', description: 'Apply safe session-level DB settings for the current connection.')]
final class DatabaseSessionFixCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->connection->executeStatement("SET time_zone = '+00:00'");
        $this->connection->executeStatement("SET SESSION sql_mode = CONCAT_WS(',', @@sql_mode, 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE')");

        $row = $this->connection->fetchAssociative("SELECT @@session.time_zone AS session_tz, @@sql_mode AS sql_mode");
        $row = is_array($row) ? $row : [];

        $io->success('Session-level database settings applied.');
        $io->definitionList(
            ['Session timezone' => is_scalar($row['session_tz'] ?? null) ? (string) $row['session_tz'] : ''],
            ['SQL mode' => is_scalar($row['sql_mode'] ?? null) ? (string) $row['sql_mode'] : ''],
        );

        return Command::SUCCESS;
    }
}

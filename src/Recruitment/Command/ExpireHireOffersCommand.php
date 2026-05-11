<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:hire-offers:expire',
    description: 'Expire pending hire offers older than N days (default: 30)',
)]
final class ExpireHireOffersCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days after which to expire offers', 30);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        if ($days <= 0) {
            $io->error('Days must be a positive integer.');

            return Command::FAILURE;
        }

        $expired = (int) $this->connection->executeStatement(
            "UPDATE hire_offers SET status = 'expired'
             WHERE status = 'pending'
               AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days],
        );

        $io->success(sprintf('Expired %d hire offer(s) older than %d days.', $expired, $days));

        return Command::SUCCESS;
    }
}

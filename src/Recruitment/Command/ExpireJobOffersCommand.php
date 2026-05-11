<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:expire-job-offers', description: 'Close job offers past their expiry date')]
final class ExpireJobOffersCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $byDate = (int) $this->connection->executeStatement(
            "UPDATE job_offers SET is_expired = 1, status = 'CLOSED'
             WHERE expires_at IS NOT NULL AND expires_at < NOW() AND is_expired = 0"
        );

        $byAge = (int) $this->connection->executeStatement(
            "UPDATE job_offers SET is_expired = 1, status = 'CLOSED'
             WHERE feed_source IS NOT NULL
               AND posted_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
               AND is_expired = 0
               AND id NOT IN (
                   SELECT DISTINCT job_offer_id FROM job_applications
                   WHERE job_offer_id IS NOT NULL
               )"
        );

        $byManual = (int) $this->connection->executeStatement(
            "UPDATE job_offers SET is_expired = 1, status = 'CLOSED'
             WHERE feed_source IS NULL
               AND posted_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
               AND is_expired = 0"
        );

        $io->success(sprintf(
            'Expired: %d by explicit date, %d feed jobs (7d old), %d manual jobs (30d old)',
            $byDate,
            $byAge,
            $byManual
        ));

        return Command::SUCCESS;
    }
}

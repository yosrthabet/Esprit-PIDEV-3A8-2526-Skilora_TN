<?php

declare(strict_types=1);

namespace App\Finance\Command;

use App\Finance\Service\ExchangeRateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:exchange-rates:refresh',
    description: 'Fetch latest exchange rates from the internet and store them',
)]
class RefreshExchangeRatesCommand extends Command
{
    public function __construct(private readonly ExchangeRateService $exchangeRateService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->exchangeRateService->refreshAll();

        $io->success(sprintf('Refreshed %d exchange rate(s).', $count));

        return Command::SUCCESS;
    }
}

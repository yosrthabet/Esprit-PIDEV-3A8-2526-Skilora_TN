<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use App\Recruitment\Service\ApplicationsTableSchemaPatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Corrige SQLSTATE[42S22] Unknown column 'cv_path' : ajoute les colonnes manquantes sur `applications`.
 */
#[AsCommand(
    name: 'app:recruitment:ensure-applications-schema',
    description: 'Ajoute cv_path et colonnes manquantes sur la table applications (alignement entité / base)',
)]
final class EnsureApplicationsSchemaCommand extends Command
{
    public function __construct(
        private readonly ApplicationsTableSchemaPatcher $applicationsTableSchemaPatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'truncate-if-incomplete',
            null,
            InputOption::VALUE_NONE,
            'Si des lignes existent sans colonnes job_offer_id/candidate_id, vide applications et interviews puis ajoute les colonnes (perte de données — dev uniquement)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->applicationsTableSchemaPatcher->applicationsTableExists()) {
            $io->error('La table applications n’existe pas. Exécutez d’abord : php bin/console app:install-job-applications-table');

            return Command::FAILURE;
        }

        $truncate = (bool) $input->getOption('truncate-if-incomplete');
        $added = $this->applicationsTableSchemaPatcher->ensureMissingCoreColumns($truncate);

        if ($added === []) {
            $io->success('La table applications est déjà alignée sur l’entité (colonnes présentes).');

            return Command::SUCCESS;
        }

        $io->success('Colonnes ajoutées : '.implode(', ', $added).'. Rechargez /employer/candidatures.');

        return Command::SUCCESS;
    }
}

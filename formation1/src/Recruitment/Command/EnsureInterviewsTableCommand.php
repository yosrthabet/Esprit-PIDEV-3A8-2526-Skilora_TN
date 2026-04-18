<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use App\Recruitment\Service\InterviewsTableColumnsEnsurer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Aligne la table {@code interviews} sur l’entité Doctrine (colonnes {@code interview_format}, {@code interview_date}, etc.).
 * À lancer si : {@code Unknown column 'i0_.interview_format'} ou migrations non appliquées.
 */
#[AsCommand(
    name: 'app:recruitment:ensure-interviews-table',
    description: 'Ajoute les colonnes manquantes sur la table interviews (interview_format, interview_date, …)',
)]
final class EnsureInterviewsTableCommand extends Command
{
    public function __construct(
        private readonly InterviewsTableColumnsEnsurer $interviewsTableColumnsEnsurer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $added = $this->interviewsTableColumnsEnsurer->ensureDoctrineColumns();
        if ($added === []) {
            $io->success('Table interviews : colonnes Doctrine déjà présentes (ou table absente).');

            return Command::SUCCESS;
        }

        $io->success('Colonnes ajoutées : '.implode(', ', $added).'. Rechargez la page Entretiens / Planifier.');

        return Command::SUCCESS;
    }
}

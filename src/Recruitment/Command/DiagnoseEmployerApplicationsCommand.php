<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use App\Entity\User;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\CompanyRepository;
use App\Recruitment\Repository\JobOfferRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Affiche les ids `companies` (SQL owner_id) et les lignes `applications` visibles pour l’employeur.
 */
#[AsCommand(
    name: 'app:recruitment:diagnose-employer-applications',
    description: 'Diagnostic : ids companies (SQL) et candidatures (SELECT applications pour cet employeur)',
)]
final class DiagnoseEmployerApplicationsCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ApplicationsTableGateway $applicationsTableGateway,
        private readonly CompanyRepository $companyRepository,
        private readonly JobOfferRepository $jobOfferRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse e-mail du compte employeur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getOption('email');
        if ($email === '') {
            $io->error('Utilisez --email=votre@employeur.com');

            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $io->error('Utilisateur introuvable pour cet e-mail.');

            return Command::FAILURE;
        }

        $uid = $user->getId();
        if ($uid === null) {
            $io->error('Utilisateur sans id.');

            return Command::FAILURE;
        }

        $companyIds = $this->companyRepository->findCompanyIdsByOwnerUserId($uid);
        $apps = $this->applicationsTableGateway->fetchByEmployerOwnerUserId($uid);

        $io->section('Données alignées sur les tables');
        $io->listing([
            'users.id (employeur) : '.$uid,
            'companies.id (SQL `WHERE owner_id = '.$uid.'`) : '.($companyIds === [] ? '(aucune)' : implode(', ', $companyIds)),
            'Lignes applications (SELECT a.* …) : '.\count($apps),
        ]);

        if ($companyIds === []) {
            $io->warning('Aucune ligne `companies` pour ce owner_id — créez / associez une fiche entreprise.');

            return Command::SUCCESS;
        }

        if ($apps !== []) {
            $rows = [];
            foreach ($apps as $a) {
                $jid = (int) ($a['job_offer_id'] ?? 0);
                $job = $this->jobOfferRepository->find($jid);
                $rows[] = [
                    (string) ($a['id'] ?? ''),
                    (string) $jid,
                    (string) ($job?->getCompany()?->getId() ?? ''),
                    $job?->getTitle() ?? '—',
                    (string) ($a['candidate_id'] ?? ''),
                ];
            }
            $io->table(['applications.id', 'job_offers.id', 'company_id', 'Titre offre', 'candidate_id'], $rows);
        }

        $breakdown = $this->applicationsTableGateway->fetchEmployerCandidateBreakdown($uid);
        $io->section('Candidats distincts (agrégat SQL — même périmètre que la page « Candidatures »)');
        if ($breakdown === []) {
            $io->text('Aucune ligne.');
        } else {
            $io->table(
                ['users.id (candidate_id)', 'Nb candidatures', 'Nom affiché', 'E-mail'],
                array_map(static fn (array $b): array => [
                    (string) $b['candidate_user_id'],
                    (string) $b['application_count'],
                    $b['candidate_name'],
                    $b['candidate_email'] ?? '—',
                ], $breakdown),
            );
        }

        $distinct = \count($breakdown);
        $total = \count($apps);
        $io->text([
            '',
            '<info>À propos du filtre SQL</info>',
            'La liste employeur part bien de la table <comment>applications</comment>, joint <comment>job_offers</comment> + <comment>users</comment>,',
            'et <options=bold>ne contient pas</> de condition du type <comment>WHERE candidate_id = un seul id</comment>.',
            'Si vous ne voyez qu’un seul nom alors que « beaucoup d’utilisateurs » existent dans <comment>users</comment>,',
            'c’est en général que <options=bold>seules les lignes applications pour vos offres pointent vers ce compte</> (données),',
            'ou que d’autres candidats n’ont tout simplement pas postulé à vos offres.',
            '',
            \sprintf(
                'Pour cet employeur : <comment>%d</comment> candidat(s) distinct(s) dans les données, <comment>%d</comment> ligne(s) <comment>applications</comment>.',
                $distinct,
                $total,
            ),
        ]);

        if ($distinct === 1 && $total > 1) {
            $io->warning(
                'Plusieurs candidatures mais un seul candidate_id : la même personne a postulé à plusieurs offres, ou les données de test ont été générées avec un seul compte.',
            );
            $io->text('Pour ajouter des candidatures d’autres comptes (démo) : <comment>php bin/console app:recruitment:spread-demo-applications --employer-email='.$email.'</comment>');
        }

        $io->note('Si une candidature manque : vérifiez `job_offers.company_id` (ou `company_name`) pour cette offre (doit correspondre à votre entreprise).');

        return Command::SUCCESS;
    }
}

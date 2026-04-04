<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use App\Entity\User;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Offres avec au moins une candidature dont `company_id` n’est pas une entreprise possédée,
 * mais `company_name` correspond à une fiche possédée → réassigne `company_id`.
 */
#[AsCommand(
    name: 'app:recruitment:repair-offer-company-fk',
    description: 'Corrige job_offers.company_id quand le libellé correspond à votre entreprise mais la FK pointe ailleurs',
)]
final class RepairJobOfferCompanyFkCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse e-mail du compte employeur');
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Enregistrer en base (sans cette option, affichage seul)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getOption('email');
        $apply = (bool) $input->getOption('apply');

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

        $ownedIds = $this->companyRepository->findCompanyIdsByOwnerUserId($uid);
        if ($ownedIds === []) {
            $io->success('Aucune entreprise (SQL) — rien à corriger.');

            return Command::SUCCESS;
        }

        $conn = $this->entityManager->getConnection();
        $placeholders = implode(',', array_fill(0, \count($ownedIds), '?'));
        $sql = <<<SQL
            SELECT DISTINCT jo.id, jo.company_id, jo.company_name
            FROM job_offers jo
            INNER JOIN applications ja ON ja.job_offer_id = jo.id
            WHERE jo.company_id NOT IN ($placeholders)
            SQL;

        $rows = $conn->fetchAllAssociative($sql, $ownedIds);

        if ($rows === []) {
            $io->success('Aucune offre avec candidature dont company_id serait hors de vos entreprises.');

            return Command::SUCCESS;
        }

        $planned = [];
        foreach ($rows as $row) {
            $jobId = (int) $row['id'];
            $job = $this->entityManager->find(JobOffer::class, $jobId);
            if ($job === null) {
                continue;
            }

            $nameRaw = $row['company_name'];
            $name = \is_string($nameRaw) ? $nameRaw : null;
            $correct = $this->companyRepository->findFirstOwnedCompanyMatchingDisplayName($user, $name);
            if ($correct === null) {
                continue;
            }

            $currentId = $job->getCompany()?->getId();
            if ($currentId === $correct->getId()) {
                continue;
            }

            $planned[] = [$job, $correct];
        }

        if ($planned === []) {
            $io->success('Aucune correction automatique possible (libellé company_name ne correspond à aucune de vos fiches).');

            return Command::SUCCESS;
        }

        foreach ($planned as [$job, $correct]) {
            $io->writeln(\sprintf(
                'Offre #%d : company_id %s → %s (%s)',
                $job->getId() ?? 0,
                $job->getCompany()?->getId() !== null ? (string) $job->getCompany()->getId() : 'null',
                (string) $correct->getId(),
                $correct->getName(),
            ));
            if ($apply) {
                $job->setCompany($correct);
            }
        }

        if (!$apply) {
            $io->note('Aucune écriture. Relancez avec --apply pour enregistrer les changements.');

            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success('job_offers.company_id mis à jour.');

        return Command::SUCCESS;
    }
}

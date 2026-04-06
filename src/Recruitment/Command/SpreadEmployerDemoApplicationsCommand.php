<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\CompanyRepository;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Service\CandidateProfileIdResolver;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ajoute des lignes {@code applications} pour d’autres comptes candidats (pool avec profil),
 * afin que la page employeur affiche plusieurs noms — sans modifier la requête SQL de liste.
 */
#[AsCommand(
    name: 'app:recruitment:spread-demo-applications',
    description: 'Crée des candidatures manquantes (offres OPEN × candidats du pool) pour diversifier l’affichage employeur',
)]
final class SpreadEmployerDemoApplicationsCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly JobOfferRepository $jobOfferRepository,
        private readonly ApplicationsTableGateway $applicationsTableGateway,
        private readonly CandidateProfileIdResolver $candidateProfileIdResolver,
        private readonly string $cvUploadDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('employer-email', null, InputOption::VALUE_REQUIRED, 'Compte employeur (ex. emp@gmail.com)')
            ->addOption('max', null, InputOption::VALUE_OPTIONAL, 'Nombre max de nouvelles candidatures à créer', '25')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Afficher sans enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $employerEmail = (string) $input->getOption('employer-email');
        $max = max(1, (int) $input->getOption('max'));
        $dryRun = (bool) $input->getOption('dry-run');

        $employer = $this->userRepository->findOneBy(['email' => $employerEmail]);
        if (!$employer instanceof User) {
            $io->error('Employeur introuvable pour cet e-mail.');

            return Command::FAILURE;
        }

        if (!\in_array('ROLE_EMPLOYER', $employer->getRoles(), true)) {
            $io->error('Ce compte n’a pas ROLE_EMPLOYER.');

            return Command::FAILURE;
        }

        $companies = $this->companyRepository->findBy(['owner' => $employer], ['id' => 'ASC']);
        if ($companies === []) {
            $io->error('Aucune entreprise pour cet employeur (companies.owner_id).');

            return Command::FAILURE;
        }

        $jobs = $this->jobOfferRepository->createQueryBuilder('j')
            ->where('j.company IN (:companies)')
            ->andWhere('j.status = :open')
            ->setParameter('companies', $companies)
            ->setParameter('open', 'OPEN')
            ->orderBy('j.id', 'ASC')
            ->getQuery()
            ->getResult();

        if ($jobs === []) {
            $io->warning('Aucune offre OPEN pour ces entreprises.');

            return Command::SUCCESS;
        }

        $pool = $this->buildCandidatePool($employer);
        if ($pool === []) {
            $io->error(
                'Aucun compte candidat avec une ligne `profiles` (hors employeur). '
                .'Créez des profils ou des utilisateurs candidats.',
            );

            return Command::FAILURE;
        }

        $relativeCv = $this->ensureDemoCvFile($dryRun, $io);
        if ($relativeCv === null && !$dryRun) {
            return Command::FAILURE;
        }

        $created = 0;
        foreach ($jobs as $job) {
            $jid = $job->getId();
            if ($jid === null) {
                continue;
            }
            foreach ($pool as $candidate) {
                if ($created >= $max) {
                    break 2;
                }
                $cuid = $candidate->getId();
                if ($cuid === null) {
                    continue;
                }
                if ($this->applicationsTableGateway->existsForJobOfferAndCandidate($jid, $cuid)) {
                    continue;
                }

                $profileId = $this->candidateProfileIdResolver->findProfileIdForUserId($cuid);
                if ($profileId === null) {
                    continue;
                }

                if ($dryRun) {
                    $io->writeln(\sprintf('[dry-run] Créerait candidature : offre #%d, candidat %s (#%d)', $jid, $candidate->getEmail() ?? '?', $cuid));
                    ++$created;

                    continue;
                }

                \assert($relativeCv !== null);
                $this->applicationsTableGateway->insertApplication(
                    $jid,
                    $profileId,
                    $cuid,
                    ApplicationStatus::IN_PROGRESS,
                    $relativeCv,
                    'Candidature de démonstration (spread-demo-applications).',
                );
                ++$created;
                $io->writeln(\sprintf('Créé : offre #%d ← candidat #%d (%s)', $jid, $cuid, $candidate->getEmail() ?? '?'));
            }
        }

        if ($created === 0) {
            $io->success('Rien à ajouter : chaque couple (offre, candidat) du pool existe déjà, ou la limite est 0.');

            return Command::SUCCESS;
        }

        $io->success(\sprintf(
            '%s %d candidature(s). Rechargez /employer/candidatures.',
            $dryRun ? 'Simulation :' : 'Enregistré :',
            $created,
        ));
        if (!$dryRun) {
            $io->note('Diagnostic : php bin/console app:recruitment:diagnose-employer-applications --email='.$employerEmail);
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<User>
     */
    private function buildCandidatePool(User $employer): array
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.email IS NOT NULL')
            ->andWhere('u.email != :emp')
            ->setParameter('emp', (string) $employer->getEmail())
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();

        $pool = [];
        foreach ($users as $u) {
            if (!$u instanceof User) {
                continue;
            }
            if (\in_array('ROLE_EMPLOYER', $u->getRoles(), true)) {
                continue;
            }
            $uid = $u->getId();
            if ($uid === null) {
                continue;
            }
            if ($this->candidateProfileIdResolver->findProfileIdForUserId($uid) === null) {
                continue;
            }
            $pool[] = $u;
        }

        return $pool;
    }

    private function ensureDemoCvFile(bool $dryRun, SymfonyStyle $io): ?string
    {
        if ($dryRun) {
            return 'demo/dry-run.txt';
        }

        $subDir = (new \DateTimeImmutable())->format('Y/m');
        $relativeCv = $subDir.'/spread_demo_'.bin2hex(random_bytes(6)).'.txt';
        $targetDir = $this->cvUploadDir.'/'.$subDir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            $io->error('Impossible de créer le répertoire CV : '.$targetDir);

            return null;
        }
        file_put_contents($this->cvUploadDir.'/'.$relativeCv, "CV démo (spread-demo-applications)\n");

        return $relativeCv;
    }
}

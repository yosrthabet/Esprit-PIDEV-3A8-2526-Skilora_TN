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
 * Remplit la table `applications` pour le dev / démo quand elle est vide (ex. après truncate du schéma).
 */
#[AsCommand(
    name: 'app:recruitment:seed-applications',
    description: 'Crée des candidatures de démo pour les offres OPEN de l’employeur (fichier CV factice)',
)]
final class SeedEmployerApplicationsCommand extends Command
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
            ->addOption('candidate-email', null, InputOption::VALUE_OPTIONAL, 'Un seul candidat pour toutes les lignes (sinon : répartition sur tous les comptes éligibles avec profil)')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Nombre max d’offres OPEN à couvrir', '5')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Afficher sans enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $employerEmail = (string) $input->getOption('employer-email');
        $candidateEmailOpt = $input->getOption('candidate-email');
        $candidateEmail = \is_string($candidateEmailOpt) ? $candidateEmailOpt : null;
        $limit = max(1, (int) $input->getOption('limit'));
        $dryRun = (bool) $input->getOption('dry-run');

        $employer = $this->userRepository->findOneBy(['email' => $employerEmail]);
        if (!$employer instanceof User) {
            $io->error('Employeur introuvable pour cet e-mail.');

            return Command::FAILURE;
        }

        if (!\in_array('ROLE_EMPLOYER', $employer->getRoles(), true)) {
            $io->error('Ce compte n’est pas un employeur (ROLE_EMPLOYER).');

            return Command::FAILURE;
        }

        $companies = $this->companyRepository->findBy(['owner' => $employer], ['id' => 'ASC']);
        if ($companies === []) {
            $io->error('Aucune entreprise pour cet employeur (companies.owner_id).');

            return Command::FAILURE;
        }

        $pool = $this->buildCandidatePool($employer, $candidateEmail);
        if ($pool === []) {
            $io->error(
                'Aucun compte candidat utilisable avec une ligne `profiles`. '
                .'Passez --candidate-email=… pour un utilisateur précis, ou créez des profils candidats.',
            );

            return Command::FAILURE;
        }

        $jobs = $this->jobOfferRepository->createQueryBuilder('j')
            ->where('j.company IN (:companies)')
            ->andWhere('j.status = :open')
            ->setParameter('companies', $companies)
            ->setParameter('open', 'OPEN')
            ->orderBy('j.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if ($jobs === []) {
            $io->warning('Aucune offre OPEN pour ces entreprises.');

            return Command::SUCCESS;
        }

        $subDir = (new \DateTimeImmutable())->format('Y/m');
        $relativeCv = $subDir.'/seed_'.bin2hex(random_bytes(6)).'.txt';
        $targetDir = $this->cvUploadDir.'/'.$subDir;
        if (!$dryRun) {
            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                $io->error('Impossible de créer le répertoire CV : '.$targetDir);

                return Command::FAILURE;
            }
            file_put_contents($this->cvUploadDir.'/'.$relativeCv, "CV de démonstration (seed)\n");
        }

        $created = 0;
        $poolSize = \count($pool);
        $idx = 0;
        foreach ($jobs as $job) {
            $candidate = $pool[$idx % $poolSize];
            ++$idx;

            $jid = $job->getId();
            $cuid = $candidate->getId();
            if ($jid === null || $cuid === null) {
                continue;
            }

            if ($this->applicationsTableGateway->existsForJobOfferAndCandidate($jid, $cuid)) {
                $io->writeln(\sprintf(
                    'Déjà postulé (%s) : offre #%d — ignoré.',
                    $candidate->getEmail() ?? '?',
                    $jid,
                ));

                continue;
            }

            if ($dryRun) {
                $io->writeln(\sprintf(
                    '[dry-run] Créerait candidature : offre #%d, candidat %s',
                    $jid,
                    $candidate->getEmail() ?? '?',
                ));
                ++$created;

                continue;
            }

            $profileId = $this->candidateProfileIdResolver->findProfileIdForUserId($cuid);
            if ($profileId === null) {
                $io->warning(\sprintf(
                    'Pas de ligne `profiles` pour l’utilisateur #%d (%s) — ignoré.',
                    $cuid,
                    $candidate->getEmail() ?? '?',
                ));

                continue;
            }

            $this->applicationsTableGateway->insertApplication(
                $jid,
                $profileId,
                $cuid,
                ApplicationStatus::IN_PROGRESS,
                $relativeCv,
                'Candidature générée par la commande app:recruitment:seed-applications (démo).',
            );
            ++$created;
        }

        if ($created === 0) {
            $io->warning('Aucune nouvelle candidature (déjà postulé sur toutes les offres sélectionnées).');

            return Command::SUCCESS;
        }

        $mode = $candidateEmail !== null && $candidateEmail !== ''
            ? 'un seul candidat'
            : \sprintf('%d candidat(s) distinct(s) (rotation)', $poolSize);

        $io->success(\sprintf(
            '%s %d candidature(s) (%s) — recharger /employer/candidatures.',
            $dryRun ? 'Simulé :' : 'Enregistré :',
            $created,
            $mode,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return list<User>
     */
    private function buildCandidatePool(User $employer, ?string $candidateEmail): array
    {
        if ($candidateEmail !== null && $candidateEmail !== '') {
            $u = $this->userRepository->findOneBy(['email' => $candidateEmail]);
            if ($u instanceof User && !\in_array('ROLE_EMPLOYER', $u->getRoles(), true)) {
                return [$u];
            }

            return [];
        }

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
}

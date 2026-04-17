<?php

namespace App\Recruitment\Command;

use App\Recruitment\Service\ApplicationsTableSchemaPatcher;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Crée la table `applications` (candidatures) si elle n’existe pas.
 * Si l’ancienne table `job_applications` est encore présente, exécutez : php bin/console doctrine:migrations:migrate
 */
#[AsCommand(
    name: 'app:install-job-applications-table',
    description: 'Crée la table applications pour les candidatures (offre + utilisateur + CV)',
)]
final class InstallJobApplicationsTableCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ApplicationsTableSchemaPatcher $applicationsTableSchemaPatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['applications'])) {
            $added = $this->applicationsTableSchemaPatcher->ensureMissingCoreColumns();
            $io->success(
                $added !== []
                    ? 'Table applications : colonnes ajoutées ('.implode(', ', $added).'). Rechargez /employer/candidatures.'
                    : 'La table applications est déjà à jour (cv_path, etc.).',
            );

            return Command::SUCCESS;
        }

        if ($sm->tablesExist(['job_applications'])) {
            $io->warning('L’ancienne table job_applications est encore présente. Renommez-la en exécutant : php bin/console doctrine:migrations:migrate');

            return Command::FAILURE;
        }

        $this->connection->executeStatement(<<<'SQL'
CREATE TABLE applications (
    id INT AUTO_INCREMENT NOT NULL,
    cv_path VARCHAR(500) NOT NULL,
    cover_letter LONGTEXT DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'IN_PROGRESS' NOT NULL,
    applied_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    applied_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    job_offer_id INT NOT NULL,
    candidate_id INT NOT NULL,
    candidate_profile_id INT DEFAULT NULL,
    INDEX IDX_JOB_APP_OFFER (job_offer_id),
    INDEX IDX_JOB_APP_CANDIDATE (candidate_id),
    UNIQUE INDEX uniq_application_candidate (job_offer_id, candidate_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
SQL);

        $this->connection->executeStatement(
            'ALTER TABLE applications ADD CONSTRAINT FK_JOB_APP_OFFER FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE',
        );
        $this->connection->executeStatement(
            'ALTER TABLE applications ADD CONSTRAINT FK_JOB_APP_USER FOREIGN KEY (candidate_id) REFERENCES users (id) ON DELETE CASCADE',
        );

        $io->success('Table applications créée avec succès. Rechargez /offres et Mes candidatures.');

        return Command::SUCCESS;
    }
}

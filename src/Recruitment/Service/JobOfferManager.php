<?php

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\CompanyRepository;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class JobOfferManager
{
    private static bool $jobOffersIdBootstrapped = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmployerContext $employerContext,
        private readonly CompanyRepository $companyRepository,
    ) {
    }

    public function createForEmployer(User $user, JobOffer $jobOffer): void
    {
        $this->ensureJobOffersIdAutoIncrement();
        $company = $this->requireCompany($user);
        $this->normalizeOffer($jobOffer);
        $now = new \DateTimeImmutable();
        if ($jobOffer->getPostedDate() === null) {
            $jobOffer->setPostedDate($now);
        }
        $jobOffer->setUpdatedAt($now);
        $jobOffer->setCompany($company);
        $jobOffer->setCompanyName($company->getName());

        $this->entityManager->persist($jobOffer);
        $this->entityManager->flush();
    }

    public function updateForEmployer(User $user, JobOffer $jobOffer): void
    {
        $this->assertEmployerOwns($user, $jobOffer);
        $this->normalizeOffer($jobOffer);
        $jobOffer->setUpdatedAt(new \DateTimeImmutable());
        $company = $jobOffer->getCompany();
        if ($company !== null) {
            $jobOffer->setCompanyName($company->getName());
        }

        $this->entityManager->flush();
    }

    public function deleteForEmployer(User $user, JobOffer $jobOffer): void
    {
        $this->assertEmployerOwns($user, $jobOffer);
        $this->entityManager->remove($jobOffer);
        $this->entityManager->flush();
    }

    public function closeForEmployer(User $user, JobOffer $jobOffer): void
    {
        $this->assertEmployerOwns($user, $jobOffer);
        $jobOffer->setStatus('CLOSED');
        $jobOffer->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function assertEmployerOwns(User $user, JobOffer $jobOffer): void
    {
        if (!$this->companyRepository->employerOwnsJobOfferDisplay($user, $jobOffer)) {
            throw new AccessDeniedHttpException('You cannot manage this job offer.');
        }
    }

    public function requireCompany(User $user): Company
    {
        $company = $this->employerContext->getCompanyForEmployer($user);
        if ($company === null) {
            throw new AccessDeniedHttpException('No company is linked to your employer account.');
        }

        return $company;
    }

    private function normalizeOffer(JobOffer $jobOffer): void
    {
        foreach (['MinSalary', 'MaxSalary'] as $suffix) {
            $getter = 'get'.$suffix;
            $setter = 'set'.$suffix;
            $v = $jobOffer->$getter();
            if ($v === null || $v === '') {
                $jobOffer->$setter(null);
            } elseif (is_numeric($v)) {
                $jobOffer->$setter(number_format((float) $v, 2, '.', ''));
            }
        }
    }

    /**
     * Corrige les bases legacy ou `job_offers.id` n'est pas AUTO_INCREMENT
     * (sinon Doctrine peut lever "No identity value was generated...").
     */
    private function ensureJobOffersIdAutoIncrement(): void
    {
        if (self::$jobOffersIdBootstrapped) {
            return;
        }
        self::$jobOffersIdBootstrapped = true;

        $conn = $this->entityManager->getConnection();
        $sm = $conn->createSchemaManager();
        if (!$sm->tablesExist(['job_offers'])) {
            return;
        }

        $platform = $conn->getDatabasePlatform();
        if (!$platform instanceof AbstractMySQLPlatform) {
            return;
        }

        $row = $conn->fetchAssociative(
            <<<'SQL'
            SELECT COLUMN_TYPE, EXTRA, COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'job_offers'
              AND COLUMN_NAME = 'id'
            SQL,
        );
        if (!\is_array($row)) {
            return;
        }

        if (stripos((string) ($row['EXTRA'] ?? ''), 'auto_increment') !== false) {
            return;
        }

        $colType = trim((string) ($row['COLUMN_TYPE'] ?? 'int(11)'));
        if ($colType === '') {
            $colType = 'int(11)';
        }

        $modifySql = "ALTER TABLE job_offers MODIFY id {$colType} NOT NULL AUTO_INCREMENT";
        try {
            $conn->executeStatement($modifySql);
        } catch (\Throwable) {
            $key = (string) ($row['COLUMN_KEY'] ?? '');
            if ($key !== 'PRI' && $key !== 'UNI') {
                try {
                    $conn->executeStatement('ALTER TABLE job_offers ADD PRIMARY KEY (id)');
                    $conn->executeStatement($modifySql);
                } catch (\Throwable) {
                }
            }
        }
    }
}

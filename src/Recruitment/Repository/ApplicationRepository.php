<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Application> */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function existsForUserAndJob(User $user, JobOffer $jobOffer): bool
    {
        return $this->findOneBy(['candidate' => $user, 'jobOffer' => $jobOffer]) !== null;
    }

    /** @return list<Application> */
    public function findForCandidate(User $candidate): array
    {
        /** @var list<Application> $applications */
        $applications = $this->createQueryBuilder('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->leftJoin('j.company', 'c')->addSelect('c')
            ->where('a.candidate = :candidate')
            ->setParameter('candidate', $candidate)
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $applications;
    }

    /** @return list<Application> */
    public function findForCompany(Company $company, int $limit = 50): array
    {
        /** @var list<Application> $applications */
        $applications = $this->createQueryBuilder('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->join('a.candidate', 'u')->addSelect('u')
            ->where('j.company = :company')
            ->setParameter('company', $company)
            ->orderBy('a.appliedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $applications;
    }
}

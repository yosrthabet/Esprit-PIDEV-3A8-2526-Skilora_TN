<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobInterview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<JobInterview> */
class JobInterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobInterview::class);
    }

    public function findOneForApplication(Application $application): ?JobInterview
    {
        return $this->findOneBy(['application' => $application]);
    }

    /** @return list<JobInterview> */
    public function findForCandidate(User $candidate): array
    {
        /** @var list<JobInterview> $interviews */
        $interviews = $this->createQueryBuilder('i')
            ->join('i.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->where('a.candidate = :candidate')
            ->setParameter('candidate', $candidate)
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $interviews;
    }

    /** @return list<JobInterview> */
    public function findForCompany(Company $company): array
    {
        /** @var list<JobInterview> $interviews */
        $interviews = $this->createQueryBuilder('i')
            ->join('i.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->join('a.candidate', 'u')->addSelect('u')
            ->where('j.company = :company')
            ->setParameter('company', $company)
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $interviews;
    }
}

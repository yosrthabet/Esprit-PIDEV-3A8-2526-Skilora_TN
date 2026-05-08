<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\HireOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<HireOffer> */
class HireOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HireOffer::class);
    }

    public function findOneForApplication(Application $application): ?HireOffer
    {
        return $this->findOneBy(['application' => $application], ['createdAt' => 'DESC']);
    }

    /** @return list<HireOffer> */
    public function findForCandidate(User $candidate): array
    {
        /** @var list<HireOffer> $offers */
        $offers = $this->createQueryBuilder('h')
            ->join('h.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->where('a.candidate = :candidate')
            ->setParameter('candidate', $candidate)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $offers;
    }

    /** @return list<HireOffer> */
    public function findForCompany(Company $company): array
    {
        /** @var list<HireOffer> $offers */
        $offers = $this->createQueryBuilder('h')
            ->join('h.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->where('j.company = :company')
            ->setParameter('company', $company)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $offers;
    }
}

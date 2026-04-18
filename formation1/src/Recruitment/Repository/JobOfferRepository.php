<?php

namespace App\Recruitment\Repository;

use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobOffer>
 */
class JobOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOffer::class);
    }

    /**
     * @return JobOffer[]
     */
    public function findByCompanyOrdered(Company $company): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.company = :company')
            ->setParameter('company', $company)
            ->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countOpenByCompany(Company $company): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.company = :company')
            ->andWhere('j.status = :open')
            ->setParameter('company', $company)
            ->setParameter('open', 'OPEN')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param 'all'|'open'|'closed'|'draft' $filter
     *
     * @return JobOffer[]
     */
    public function findByCompanyFiltered(
        Company $company,
        string $filter = 'all',
        ?string $search = null,
        ?string $workType = null,
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->where('j.company = :company')
            ->setParameter('company', $company)
            ->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC');

        if ($filter === 'open') {
            $qb->andWhere('j.status = :st')->setParameter('st', 'OPEN');
        } elseif ($filter === 'closed') {
            $qb->andWhere('j.status = :st')->setParameter('st', 'CLOSED');
        } elseif ($filter === 'draft') {
            $qb->andWhere('j.status = :st')->setParameter('st', 'DRAFT');
        }

        if ($workType !== null && $workType !== '') {
            $qb->andWhere('j.workType = :wt')->setParameter('wt', $workType);
        }

        if ($search !== null && $search !== '') {
            $q = '%'.mb_strtolower($search).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(j.title) LIKE :q',
                'LOWER(COALESCE(j.location, \'\')) LIKE :q',
                'LOWER(COALESCE(j.companyName, \'\')) LIKE :q',
                'LOWER(COALESCE(c.name, \'\')) LIKE :q',
            ))->setParameter('q', $q);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Offres ouvertes visibles par les candidats (toutes, y compris récentes).
     *
     * @return JobOffer[]
     */
    public function findOpenOffersForCandidates(): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.status = :open')
            ->setParameter('open', 'OPEN')
            ->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Offres ouvertes avec recherche texte + filtre optionnel sur `work_type`.
     *
     * @return JobOffer[]
     */
    public function findOpenOffersForCandidatesFiltered(?string $search = null, ?string $workType = null, int $page = 1, int $perPage = 12): array
    {
        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->where('j.status = :open')
            ->setParameter('open', 'OPEN')
            ->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($workType !== null && $workType !== '') {
            $qb->andWhere('j.workType = :wt')->setParameter('wt', $workType);
        }

        if ($search !== null && $search !== '') {
            $q = '%'.mb_strtolower($search).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(j.title) LIKE :q',
                'LOWER(COALESCE(j.location, \'\')) LIKE :q',
                'LOWER(COALESCE(j.companyName, \'\')) LIKE :q',
                'LOWER(COALESCE(c.name, \'\')) LIKE :q',
            ))->setParameter('q', $q);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count open offers matching filters (for pagination).
     */
    public function countOpenOffersForCandidatesFiltered(?string $search = null, ?string $workType = null): int
    {
        $qb = $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->leftJoin('j.company', 'c')
            ->where('j.status = :open')
            ->setParameter('open', 'OPEN');

        if ($workType !== null && $workType !== '') {
            $qb->andWhere('j.workType = :wt')->setParameter('wt', $workType);
        }

        if ($search !== null && $search !== '') {
            $q = '%'.mb_strtolower($search).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(j.title) LIKE :q',
                'LOWER(COALESCE(j.location, \'\')) LIKE :q',
                'LOWER(COALESCE(j.companyName, \'\')) LIKE :q',
                'LOWER(COALESCE(c.name, \'\')) LIKE :q',
            ))->setParameter('q', $q);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Offre ouverte pour la fiche candidat — joint l’entreprise en une requête.
     */
    public function findOpenForCandidateById(int $id): ?JobOffer
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')->addSelect('c')
            ->andWhere('j.id = :id')->setParameter('id', $id)
            ->andWhere('j.status = :open')->setParameter('open', 'OPEN')
            ->getQuery()
            ->getOneOrNullResult();
    }
}

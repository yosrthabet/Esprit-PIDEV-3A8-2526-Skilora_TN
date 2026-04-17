<?php

namespace App\Recruitment\Repository;

use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
     * Offres gérées par l’employeur : même périmètre que {@see CompanyRepository::employerOwnsJobOfferDisplay}
     * (company_id ∈ entreprises possédées OU libellé company_name qui correspond).
     *
     * @param list<int>                $ownedCompanyIds
     * @param list<string>             $ownedCompanyNamesLower noms d’entreprise (minuscules)
     * @param 'all'|'open'|'closed'|'draft' $filter
     *
     * @return JobOffer[]
     */
    public function findAccessibleToEmployerFiltered(
        array $ownedCompanyIds,
        array $ownedCompanyNamesLower,
        string $filter = 'all',
        ?string $search = null,
        ?string $workType = null,
    ): array {
        $qb = $this->createEmployerScopedQueryBuilder($ownedCompanyIds, $ownedCompanyNamesLower);
        if ($qb === null) {
            return [];
        }

        $qb->orderBy('j.postedDate', 'DESC')->addOrderBy('j.id', 'DESC');

        if ($filter === 'open') {
            $qb->andWhere('LOWER(TRIM(j.status)) = :st')->setParameter('st', 'open');
        } elseif ($filter === 'closed') {
            $qb->andWhere('LOWER(TRIM(j.status)) = :st')->setParameter('st', 'closed');
        } elseif ($filter === 'draft') {
            $qb->andWhere('LOWER(TRIM(j.status)) = :st')->setParameter('st', 'draft');
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
     * @param list<int>    $ownedCompanyIds
     * @param list<string> $ownedCompanyNamesLower
     */
    public function countOpenAccessibleToEmployer(array $ownedCompanyIds, array $ownedCompanyNamesLower): int
    {
        $qb = $this->createEmployerScopedQueryBuilder($ownedCompanyIds, $ownedCompanyNamesLower);
        if ($qb === null) {
            return 0;
        }

        return (int) $qb->select('COUNT(j.id)')
            ->andWhere('LOWER(TRIM(j.status)) = :open')
            ->setParameter('open', 'open')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param list<int>    $ownedCompanyIds
     * @param list<string> $ownedCompanyNamesLower
     *
     * @return JobOffer[]
     */
    public function findAccessibleToEmployerOrdered(array $ownedCompanyIds, array $ownedCompanyNamesLower): array
    {
        $qb = $this->createEmployerScopedQueryBuilder($ownedCompanyIds, $ownedCompanyNamesLower);
        if ($qb === null) {
            return [];
        }

        return $qb->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int>    $ownedCompanyIds
     * @param list<string> $ownedCompanyNamesLower
     */
    private function createEmployerScopedQueryBuilder(array $ownedCompanyIds, array $ownedCompanyNamesLower): ?QueryBuilder
    {
        if ($ownedCompanyIds === [] && $ownedCompanyNamesLower === []) {
            return null;
        }

        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->addSelect('c');

        $or = $qb->expr()->orX();
        if ($ownedCompanyIds !== []) {
            $or->add($qb->expr()->in('j.company', ':_scopeCompanyIds'));
            $qb->setParameter('_scopeCompanyIds', $ownedCompanyIds);
        }
        if ($ownedCompanyNamesLower !== []) {
            $or->add($qb->expr()->in('LOWER(TRIM(COALESCE(j.companyName, \'\')))', ':_scopeCompanyNames'));
            $qb->setParameter('_scopeCompanyNames', $ownedCompanyNamesLower);
        }

        $qb->where($or);

        return $qb;
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
            ->where('LOWER(TRIM(j.status)) = :open')
            ->setParameter('open', 'open')
            ->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vue globale employeur: toutes les offres (pas uniquement l'entreprise connectée).
     *
     * @param 'all'|'open'|'closed'|'draft' $filter
     *
     * @return JobOffer[]
     */
    public function findAllForEmployerViewFiltered(
        string $filter = 'all',
        ?string $search = null,
        ?string $workType = null,
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->addSelect('c')
            ->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC');

        if ($filter === 'open') {
            $qb->andWhere('LOWER(TRIM(j.status)) = :st')->setParameter('st', 'open');
        } elseif ($filter === 'closed') {
            $qb->andWhere('LOWER(TRIM(j.status)) = :st')->setParameter('st', 'closed');
        } elseif ($filter === 'draft') {
            $qb->andWhere('LOWER(TRIM(j.status)) = :st')->setParameter('st', 'draft');
        }

        if ($workType !== null && $workType !== '') {
            $qb->andWhere('UPPER(TRIM(COALESCE(j.workType, \'\'))) = :wt')
                ->setParameter('wt', strtoupper($workType));
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
     * Offres ouvertes avec recherche texte + filtre optionnel sur `work_type`.
     *
     * @return JobOffer[]
     */
    public function findOpenOffersForCandidatesFiltered(
        ?string $search = null,
        ?string $workType = null,
        int $page = 1,
        int $perPage = 12,
        string $sort = 'posted',
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->where('LOWER(TRIM(j.status)) = :open')
            ->setParameter('open', 'open')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($sort === 'salary') {
            $qb->orderBy('j.maxSalary', 'DESC')
                ->addOrderBy('j.postedDate', 'DESC')
                ->addOrderBy('j.id', 'DESC');
        } else {
            $qb->orderBy('j.postedDate', 'DESC')
                ->addOrderBy('j.id', 'DESC');
        }

        if ($workType !== null && $workType !== '') {
            $qb->andWhere('UPPER(TRIM(COALESCE(j.workType, \'\'))) = :wt')
                ->setParameter('wt', strtoupper($workType));
        }

        if ($search !== null && $search !== '') {
            $q = '%'.mb_strtolower($search).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(j.title) LIKE :q',
                'LOWER(COALESCE(j.location, \'\')) LIKE :q',
                'LOWER(COALESCE(j.companyName, \'\')) LIKE :q',
                'LOWER(COALESCE(c.name, \'\')) LIKE :q',
                'LOWER(COALESCE(j.description, \'\')) LIKE :q',
                'LOWER(COALESCE(j.requirements, \'\')) LIKE :q',
                'LOWER(COALESCE(j.skillsRequired, \'\')) LIKE :q',
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
            ->where('LOWER(TRIM(j.status)) = :open')
            ->setParameter('open', 'open');

        if ($workType !== null && $workType !== '') {
            $qb->andWhere('UPPER(TRIM(COALESCE(j.workType, \'\'))) = :wt')
                ->setParameter('wt', strtoupper($workType));
        }

        if ($search !== null && $search !== '') {
            $q = '%'.mb_strtolower($search).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(j.title) LIKE :q',
                'LOWER(COALESCE(j.location, \'\')) LIKE :q',
                'LOWER(COALESCE(j.companyName, \'\')) LIKE :q',
                'LOWER(COALESCE(c.name, \'\')) LIKE :q',
                'LOWER(COALESCE(j.description, \'\')) LIKE :q',
                'LOWER(COALESCE(j.requirements, \'\')) LIKE :q',
                'LOWER(COALESCE(j.skillsRequired, \'\')) LIKE :q',
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
            ->andWhere('LOWER(TRIM(j.status)) = :open')->setParameter('open', 'open')
            ->getQuery()
            ->getOneOrNullResult();
    }
}

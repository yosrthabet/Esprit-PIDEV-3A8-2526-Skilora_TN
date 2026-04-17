<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Entity\Application;
use App\Recruitment\Sql\ApplicationCandidateJoinParts;
use App\Recruitment\Sql\EmployerApplicationsScopeSql;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Toute lecture liste / existence part d’abord d’un {@code SELECT id FROM applications …}
 * puis hydratation de l’entité {@see Application} (table {@code applications} = source de vérité).
 */
final class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly bool $employerSeesAllCandidatures = false,
    ) {
        parent::__construct($registry, Application::class);
    }

    public function hasUserApplied(User $user, JobOffer $jobOffer): bool
    {
        return null !== $this->findOneByJobAndCandidate($jobOffer, $user);
    }

    public function findOneByJobAndCandidate(JobOffer $jobOffer, User $user): ?Application
    {
        $jid = $jobOffer->getId();
        $uid = $user->getId();
        if ($jid === null || $uid === null) {
            return null;
        }

        $conn = $this->getEntityManager()->getConnection();
        $id = $conn->fetchOne(
            'SELECT id FROM applications WHERE job_offer_id = ? AND candidate_id = ? LIMIT 1',
            [$jid, $uid],
        );

        if ($id === false) {
            return null;
        }

        return $this->find((int) $id);
    }

    public function findOneForCandidateOnJobWithInterview(JobOffer $jobOffer, User $user): ?Application
    {
        $jid = $jobOffer->getId();
        $uid = $user->getId();
        if ($jid === null || $uid === null) {
            return null;
        }

        $conn = $this->getEntityManager()->getConnection();
        $id = $conn->fetchOne(
            'SELECT id FROM applications WHERE job_offer_id = ? AND candidate_id = ? LIMIT 1',
            [$jid, $uid],
        );

        if ($id === false) {
            return null;
        }

        return $this->createQueryBuilder('a')
            ->leftJoin('a.interview', 'iv')->addSelect('iv')
            ->andWhere('a.id = :id')->setParameter('id', (int) $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Application[]
     */
    public function findByCandidateOrdered(User $user): array
    {
        $uid = $user->getId();
        if ($uid === null) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $orderBy = ApplicationCandidateJoinParts::sqlCoalesceAppliedTimestamp($conn, 'applications');
        $ids = $conn->fetchFirstColumn(
            'SELECT id FROM applications WHERE candidate_id = ? ORDER BY '.$orderBy.' DESC, id DESC',
            [$uid],
        );

        return $this->hydrateApplicationsByIdsOrdered(
            array_map(static fn ($v) => (int) $v, $ids),
            withInterview: true,
        );
    }

    /**
     * @return Application[]
     */
    public function findByCompanyOrdered(Company $company): array
    {
        $cid = $company->getId();
        if ($cid === null) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $orderBy = ApplicationCandidateJoinParts::sqlCoalesceAppliedTimestamp($conn, 'a');
        $ids = $conn->fetchFirstColumn(
            <<<SQL
            SELECT a.id FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE j.company_id = ?
            ORDER BY {$orderBy} DESC, a.id DESC
            SQL,
            [$cid],
        );

        return $this->hydrateApplicationsByIdsOrdered(
            array_map(static fn ($v) => (int) $v, $ids),
            withInterview: false,
        );
    }

    public function countByCompany(Company $company): int
    {
        $cid = $company->getId();
        if ($cid === null) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        return (int) $conn->fetchOne(
            <<<'SQL'
            SELECT COUNT(a.id) FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE j.company_id = ?
            SQL,
            [$cid],
        );
    }

    public function findByEmployerOwnerFkOnlyOrdered(User $employer): array
    {
        return $this->findByEmployerOwnerOrdered($employer);
    }

    /**
     * @return Application[]
     */
    public function findByEmployerOwnerOrdered(User $employer): array
    {
        $ids = $this->findEmployerApplicationIdsOrdered($employer, null);

        return $this->hydrateApplicationsByIdsOrdered($ids, withInterview: false);
    }

    public function countByEmployerOwner(User $employer): int
    {
        $uid = $employer->getId();
        if ($uid === null) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        if ($this->employerSeesAllCandidatures) {
            return (int) $conn->fetchOne(
                <<<'SQL'
                SELECT COUNT(a.id)
                FROM applications a
                INNER JOIN job_offers j ON j.id = a.job_offer_id
                WHERE 1=1
                SQL,
            );
        }

        $scope = EmployerApplicationsScopeSql::jobOfferOwnedByEmployer('j');

        return (int) $conn->fetchOne(
            <<<SQL
            SELECT COUNT(a.id)
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            WHERE {$scope}
            SQL,
            [$uid],
        );
    }

    /**
     * @return Application[]
     */
    public function findAcceptedByEmployerOwnerOrdered(User $employer): array
    {
        $ids = $this->findEmployerApplicationIdsOrdered($employer, ApplicationStatus::ACCEPTED);

        return $this->hydrateApplicationsByIdsOrdered($ids, withInterview: true);
    }

    /**
     * @return list<int>
     */
    private function findEmployerApplicationIdsOrdered(User $employer, ?string $applicationStatus): array
    {
        $uid = $employer->getId();
        if ($uid === null) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        if ($this->employerSeesAllCandidatures) {
            $sql = <<<'SQL'
                SELECT a.id
                FROM applications a
                INNER JOIN job_offers j ON j.id = a.job_offer_id
                WHERE 1=1
                SQL;
            $params = [];
        } else {
            $scope = EmployerApplicationsScopeSql::jobOfferOwnedByEmployer('j');
            $sql = <<<SQL
                SELECT a.id
                FROM applications a
                INNER JOIN job_offers j ON j.id = a.job_offer_id
                WHERE {$scope}
                SQL;
            $params = [$uid];
        }
        if ($applicationStatus !== null) {
            $sql .= ' AND a.status = ?';
            $params[] = $applicationStatus;
        }
        $orderBy = ApplicationCandidateJoinParts::sqlCoalesceAppliedTimestamp($conn, 'a');
        $sql .= ' ORDER BY '.$orderBy.' DESC, a.id DESC';

        $rows = $conn->fetchFirstColumn($sql, $params);

        return array_map(static fn ($v) => (int) $v, $rows);
    }

    /**
     * @param list<int> $ids
     *
     * @return Application[]
     */
    private function hydrateApplicationsByIdsOrdered(array $ids, bool $withInterview): array
    {
        if ($ids === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->select('a', 'j', 'co', 'c')
            ->innerJoin('a.jobOffer', 'j')->addSelect('j')
            ->innerJoin('j.company', 'co')->addSelect('co')
            ->leftJoin('a.candidate', 'c')->addSelect('c')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        if ($withInterview) {
            $qb->leftJoin('a.interview', 'iv')->addSelect('iv');
        }

        $applications = $qb->getQuery()->getResult();
        $order = array_flip($ids);
        usort(
            $applications,
            static function (Application $x, Application $y) use ($order): int {
                $ix = $order[$x->getId() ?? 0] ?? 0;
                $iy = $order[$y->getId() ?? 0] ?? 0;

                return $ix <=> $iy;
            },
        );

        return $applications;
    }
}

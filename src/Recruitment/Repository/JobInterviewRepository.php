<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Entity\JobInterview;
use App\Recruitment\InterviewFormat;
use App\Recruitment\Sql\ApplicationCandidateJoinParts;
use App\Recruitment\Sql\EmployerApplicationsScopeSql;
use App\Recruitment\ViewModel\EmployerInterviewTableRow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobInterview>
 */
class JobInterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobInterview::class);
    }

    public function findOneByApplicationId(int $applicationId): ?JobInterview
    {
        return $this->createQueryBuilder('i')
            ->where('i.application = :aid')
            ->setParameter('aid', $applicationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<int> $applicationIds
     *
     * @return array<int, JobInterview> application_id => interview
     */
    public function findIndexedByApplicationIds(array $applicationIds): array
    {
        if ($applicationIds === []) {
            return [];
        }

        $interviews = $this->createQueryBuilder('i')
            ->where('i.application IN (:ids)')
            ->setParameter('ids', $applicationIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($interviews as $iv) {
            if (!$iv instanceof JobInterview) {
                continue;
            }
            $aid = $iv->getApplication()?->getId();
            if ($aid !== null) {
                $out[$aid] = $iv;
            }
        }

        return $out;
    }

    /**
     * Entretiens planifiés pour ce candidat (les plus proches en premier).
     *
     * @return JobInterview[]
     */
    public function findByCandidateOrdered(User $candidate): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.application', 'a')->addSelect('a')
            ->innerJoin('a.jobOffer', 'j')->addSelect('j')
            ->leftJoin('j.company', 'co')->addSelect('co')
            ->andWhere('a.candidate = :c')
            ->andWhere('a.status = :accepted')
            ->setParameter('c', $candidate)
            ->setParameter('accepted', ApplicationStatus::ACCEPTED)
            ->orderBy('i.scheduledAt', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste employeur : candidatures {@code ACCEPTED} avec {@code LEFT JOIN interviews}.
     * Les noms de colonnes de {@code interviews} sont détectés (schémas hétérogènes : {@code scheduled_at},
     * {@code interview_date}, etc.).
     *
     * @return list<EmployerInterviewTableRow>
     */
    public function findEmployerInterviewListRows(int $ownerUserId, bool $employerSeesAllCandidatures): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sm = $conn->createSchemaManager();

        if ($employerSeesAllCandidatures) {
            $whereScope = '1=1';
            $scopeParams = [];
        } else {
            $whereScope = EmployerApplicationsScopeSql::jobOfferOwnedByEmployer('j');
            $scopeParams = [$ownerUserId];
        }

        $params = array_merge($scopeParams, [ApplicationStatus::ACCEPTED]);

        if (!$sm->tablesExist(['interviews'])) {
            return $this->fetchEmployerRowsWithoutInterviewsTable($whereScope, $params);
        }

        $colMap = $this->interviewsColumnMap();
        $qi = static function (string $alias, string $col): string {
            return '`'.str_replace('`', '``', $alias).'`.`'.str_replace('`', '``', $col).'`';
        };

        $pick = static function (array $map, array $candidates): ?string {
            foreach ($candidates as $c) {
                $k = strtolower($c);
                if (isset($map[$k])) {
                    return $map[$k];
                }
            }

            return null;
        };

        $idCol = $pick($colMap, ['id']);
        $appFkCol = $pick($colMap, [
            'application_id',
            'job_application_id',
            'app_id',
            'id_application',
            'fk_application',
            'candidature_id',
        ]);
        $schedCol = $pick($colMap, [
            'scheduled_date',
            'interview_date',
            'scheduled_at',
            'date_interview',
            'datetime_interview',
            'interview_at',
            'date_time',
            'start_at',
        ]);
        $formatCol = $pick($colMap, ['type', 'interview_format', 'format', 'interview_type', 'mode']);
        $locCol = $pick($colMap, ['location', 'lieu', 'place', 'address']);
        $notesCol = $pick($colMap, ['notes', 'note', 'comment', 'remarks']);
        $lifeCol = $pick($colMap, ['status', 'interview_status', 'lifecycle_status', 'state']);

        $idSelect = $idCol !== null ? $qi('i', $idCol).' AS interview_id' : 'NULL AS interview_id';
        $schedSelect = $schedCol !== null ? $qi('i', $schedCol).' AS scheduled_at' : 'NULL AS scheduled_at';
        $formatSelect = $formatCol !== null
            ? 'COALESCE('.$qi('i', $formatCol).", 'ONLINE') AS format"
            : "'ONLINE' AS format";
        $locSelect = $locCol !== null ? $qi('i', $locCol).' AS location' : 'NULL AS location';
        $notesSelect = $notesCol !== null ? $qi('i', $notesCol).' AS notes' : 'NULL AS notes';
        $lifeSelect = $lifeCol !== null
            ? 'COALESCE('.$qi('i', $lifeCol).", 'SCHEDULED') AS lifecycle_status"
            : "'SCHEDULED' AS lifecycle_status";

        $joinOn = $appFkCol !== null
            ? 'LEFT JOIN interviews i ON '.$qi('i', $appFkCol).' = a.id'
            : 'LEFT JOIN interviews i ON 1=0';

        $orderCoalesce = $schedCol !== null
            ? 'COALESCE('.$qi('i', $schedCol).", '9999-12-31 23:59:59')"
            : "'9999-12-31 23:59:59'";

        $caseInterviewMissing = $idCol !== null
            ? 'CASE WHEN '.$qi('i', $idCol).' IS NULL THEN 0 ELSE 1 END ASC'
            : '1 ASC';

        $joinCandUser = ApplicationCandidateJoinParts::joinUsersOnApplicationCandidate($conn);
        $appliedTs = ApplicationCandidateJoinParts::sqlCoalesceAppliedTimestamp($conn, 'a');

        $sql = <<<SQL
            SELECT
                {$idSelect},
                a.id AS application_id,
                {$schedSelect},
                {$formatSelect},
                {$locSelect},
                {$notesSelect},
                {$lifeSelect},
                {$appliedTs} AS applied_at,
                a.status AS application_status,
                j.id AS job_offer_id,
                j.title AS job_title,
                COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—') AS candidate_name,
                u.email AS candidate_email
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            {$joinCandUser}
            {$joinOn}
            WHERE {$whereScope}
              AND a.status = ?
            ORDER BY {$caseInterviewMissing},
                     {$orderCoalesce} ASC,
                     a.id DESC
            SQL;

        $raw = $conn->fetchAllAssociative($sql, $params);

        return $this->mapRowsToEmployerInterviewTableRows($raw);
    }

    /**
     * @param array<int, array<string, mixed>> $raw
     *
     * @return list<EmployerInterviewTableRow>
     */
    private function mapRowsToEmployerInterviewTableRows(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            $appliedAt = $this->coerceToDateTimeImmutable($row['applied_at'] ?? null) ?? new \DateTimeImmutable();

            $interviewIdRaw = $row['interview_id'] ?? null;
            $interviewId = $interviewIdRaw !== null && $interviewIdRaw !== ''
                ? (int) $interviewIdRaw
                : null;

            $scheduledAt = $this->coerceToDateTimeImmutable($row['scheduled_at'] ?? null);

            $fmt = (string) ($row['format'] ?? InterviewFormat::ONLINE);
            if (!InterviewFormat::isValid($fmt)) {
                $fmt = InterviewFormat::ONLINE;
            }

            $loc = $row['location'] ?? null;
            $location = \is_string($loc) && $loc !== '' ? $loc : null;

            $noteRaw = $row['notes'] ?? null;
            $notes = \is_string($noteRaw) && trim($noteRaw) !== '' ? trim($noteRaw) : null;

            $out[] = new EmployerInterviewTableRow(
                interviewId: $interviewId,
                applicationId: (int) $row['application_id'],
                scheduledAt: $scheduledAt,
                format: $fmt,
                location: $location,
                notes: $notes,
                lifecycleStatus: (string) ($row['lifecycle_status'] ?? 'SCHEDULED'),
                appliedAt: $appliedAt,
                applicationStatus: (string) ($row['application_status'] ?? ''),
                jobOfferId: (int) $row['job_offer_id'],
                jobTitle: (string) ($row['job_title'] ?? ''),
                candidateName: (string) ($row['candidate_name'] ?? '—'),
                candidateEmail: isset($row['candidate_email']) && \is_string($row['candidate_email']) ? $row['candidate_email'] : null,
            );
        }

        return $out;
    }

    private function coerceToDateTimeImmutable(mixed $v): ?\DateTimeImmutable
    {
        if ($v instanceof \DateTimeImmutable) {
            return $v;
        }
        if ($v instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($v);
        }
        if (\is_string($v) && trim($v) !== '') {
            try {
                return new \DateTimeImmutable($v);
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /**
     * @param list<mixed> $params
     *
     * @return list<EmployerInterviewTableRow>
     */
    private function fetchEmployerRowsWithoutInterviewsTable(string $whereScope, array $params): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $joinCandUser = ApplicationCandidateJoinParts::joinUsersOnApplicationCandidate($conn);
        $appliedTs = ApplicationCandidateJoinParts::sqlCoalesceAppliedTimestamp($conn, 'a');

        $sql = <<<SQL
            SELECT
                NULL AS interview_id,
                a.id AS application_id,
                NULL AS scheduled_at,
                'ONLINE' AS format,
                NULL AS location,
                NULL AS notes,
                'SCHEDULED' AS lifecycle_status,
                {$appliedTs} AS applied_at,
                a.status AS application_status,
                j.id AS job_offer_id,
                j.title AS job_title,
                COALESCE(NULLIF(TRIM(u.full_name), ''), NULLIF(TRIM(u.email), ''), u.username, '—') AS candidate_name,
                u.email AS candidate_email
            FROM applications a
            INNER JOIN job_offers j ON j.id = a.job_offer_id
            {$joinCandUser}
            WHERE {$whereScope}
              AND a.status = ?
            ORDER BY a.id DESC
            SQL;

        $raw = $conn->fetchAllAssociative($sql, $params);

        return $this->mapRowsToEmployerInterviewTableRows($raw);
    }

    /**
     * @return array<string, string> nom en minuscules => nom réel en base
     */
    private function interviewsColumnMap(): array
    {
        $sm = $this->getEntityManager()->getConnection()->createSchemaManager();
        $out = [];
        foreach ($sm->listTableColumns('interviews') as $c) {
            $out[strtolower($c->getName())] = $c->getName();
        }

        return $out;
    }
}

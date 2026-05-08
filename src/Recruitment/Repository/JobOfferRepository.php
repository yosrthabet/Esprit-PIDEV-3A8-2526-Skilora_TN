<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Enum\FeedSource;
use App\Enum\JobOfferStatus;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<JobOffer> */
class JobOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOffer::class);
    }

    /** @return list<JobOffer> */
    public function findOpenForDiscovery(?string $query, ?string $workType, ?string $source, string $sort, int $limit, int $offset = 0): array
    {
        $qb = $this->openDiscoveryQuery($query, $workType, $source);

        if ($sort === 'match') {
            $qb->orderBy('j.featured', 'DESC')->addOrderBy('j.sourceQuality', 'DESC')->addOrderBy('j.postedAt', 'DESC');
        } elseif ($sort === 'salary') {
            $qb->orderBy('j.maxSalary', 'DESC')->addOrderBy('j.postedAt', 'DESC');
        } else {
            $qb->orderBy('j.postedAt', 'DESC')->addOrderBy('j.id', 'DESC');
        }

        /** @var list<JobOffer> $jobs */
        $jobs = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return $jobs;
    }

    public function countOpenForDiscovery(?string $query, ?string $workType, ?string $source = null): int
    {
        return (int) $this->openDiscoveryQuery($query, $workType, $source)
            ->select('COUNT(j.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array{all: int, platform: int, aneti: int, reddit: int, rss: int, linkedin_rss: int} */
    public function countOpenBySource(?string $query, ?string $workType): array
    {
        $qb = $this->openDiscoveryQuery($query, $workType)
            ->select('j.feedSource AS source, COUNT(j.id) AS total')
            ->groupBy('j.feedSource');

        $counts = [
            'all' => 0,
            'platform' => 0,
            'aneti' => 0,
            'reddit' => 0,
            'rss' => 0,
            'linkedin_rss' => 0,
        ];

        foreach ($qb->getQuery()->getScalarResult() as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sourceValue = $row['source'] ?? null;
            $source = $sourceValue instanceof FeedSource ? $sourceValue->value : (is_string($sourceValue) ? $sourceValue : 'platform');
            $total = is_numeric($row['total'] ?? null) ? (int) $row['total'] : 0;
            if (!array_key_exists($source, $counts)) {
                $source = 'platform';
            }

            $counts[$source] += $total;
            $counts['all'] += $total;
        }

        return $counts;
    }

    /**
     * @param list<string> $skillNames
     * @return list<JobOffer>
     */
    public function findRecommendedForUser(User $user, array $skillNames, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.status = :open')
            ->setParameter('open', JobOfferStatus::OPEN)
            ->setMaxResults($limit * 4)
            ->orderBy('j.featured', 'DESC')
            ->addOrderBy('j.postedAt', 'DESC');

        if ($skillNames !== []) {
            $or = $qb->expr()->orX();
            foreach (array_slice($skillNames, 0, 6) as $idx => $skill) {
                $param = 'skill' . $idx;
                $or->add('LOWER(j.title) LIKE :' . $param);
                $or->add('LOWER(COALESCE(j.skillsRequired, \'\')) LIKE :' . $param);
                $qb->setParameter($param, '%' . mb_strtolower($skill) . '%');
            }
            $qb->andWhere($or);
        }

        /** @var list<JobOffer> $jobs */
        $jobs = $qb->getQuery()->getResult();

        return array_slice($jobs, 0, $limit);
    }

    /** @return list<JobOffer> */
    public function findSearchSuggestions(string $query, int $limit = 8): array
    {
        return $this->findOpenForDiscovery($query, null, null, 'match', $limit);
    }

    /** @return list<JobOffer> */
    public function findRecentForCompany(Company $company, int $limit = 8): array
    {
        /** @var list<JobOffer> $jobs */
        $jobs = $this->createQueryBuilder('j')
            ->where('j.company = :company')
            ->setParameter('company', $company)
            ->orderBy('j.postedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $jobs;
    }

    public function countOpenForCompany(Company $company): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.company = :company')
            ->andWhere('j.status = :open')
            ->setParameter('company', $company)
            ->setParameter('open', JobOfferStatus::OPEN)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<string, true> */
    public function findExistingFeedIds(FeedSource $source): array
    {
        $rows = $this->createQueryBuilder('j')
            ->select('j.feedSourceId')
            ->where('j.feedSource = :source')
            ->andWhere('j.feedSourceId IS NOT NULL')
            ->setParameter('source', $source)
            ->getQuery()
            ->getScalarResult();

        $ids = [];
        foreach ($rows as $row) {
            if (is_array($row) && is_string($row['feedSourceId'] ?? null)) {
                $ids[$row['feedSourceId']] = true;
            }
        }

        return $ids;
    }

    private function openDiscoveryQuery(?string $query, ?string $workType, ?string $source = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.status = :open')
            ->setParameter('open', JobOfferStatus::OPEN);

        if ($workType !== null && $workType !== '') {
            $qb->andWhere('j.workType = :workType')->setParameter('workType', $workType);
        }

        if ($source !== null && $source !== '') {
            if ($source === 'platform') {
                $qb->andWhere('j.feedSource IS NULL OR j.feedSource = :platformSource')
                    ->setParameter('platformSource', FeedSource::PLATFORM);
            } else {
                $feedSource = FeedSource::tryFrom($source);
                if ($feedSource !== null) {
                    $qb->andWhere('j.feedSource = :feedSource')->setParameter('feedSource', $feedSource);
                }
            }
        }

        if ($query !== null && trim($query) !== '') {
            $q = '%' . mb_strtolower(trim($query)) . '%';
            $qb->leftJoin('j.company', 'c');
            $qb->andWhere($qb->expr()->orX(
                'LOWER(j.title) LIKE :q',
                'LOWER(COALESCE(j.companyName, \'\')) LIKE :q',
                'LOWER(COALESCE(j.location, \'\')) LIKE :q',
                'LOWER(COALESCE(j.skillsRequired, \'\')) LIKE :q',
                'LOWER(COALESCE(j.description, \'\')) LIKE :q',
                'LOWER(COALESCE(c.name, \'\')) LIKE :q',
            ))->setParameter('q', $q);
        }

        return $qb;
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Repository;

use App\Entity\User;
use App\Enum\TicketCategory;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Support\Entity\SupportTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SupportTicket> */
class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    /** @return list<SupportTicket> */
    public function findForUser(User $user, ?string $query = null, ?string $status = null, ?string $category = null, ?string $priority = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->distinct()
            ->leftJoin('t.messages', 'm', 'WITH', 'm.internalNote = false')
            ->where('t.requester = :user')
            ->setParameter('user', $user)
            ->orderBy('t.updatedAt', 'DESC');

        $this->applyFilters($qb, $query, $status, $category, $priority, true);

        /** @var list<SupportTicket> $tickets */
        $tickets = $qb->getQuery()->getResult();

        return $tickets;
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        return $this->countByEnumField('status', TicketStatus::cases());
    }

    /** @return array<string, int> */
    public function countByPriority(): array
    {
        return $this->countByEnumField('priority', TicketPriority::cases());
    }

    /** @return array<string, int> */
    public function countByCategory(): array
    {
        return $this->countByEnumField('category', TicketCategory::cases());
    }

    /** @return array<string, int> */
    public function countLast7DaysVolume(): array
    {
        $days = [];
        $today = new \DateTimeImmutable('today');
        for ($i = 6; $i >= 0; --$i) {
            $days[$today->modify('-' . $i . ' days')->format('Y-m-d')] = 0;
        }

        /** @var list<array{createdAt: \DateTimeInterface|string}> $rows */
        $rows = $this->createQueryBuilder('t')
            ->select('t.createdAt')
            ->where('t.createdAt >= :start')
            ->setParameter('start', $today->modify('-6 days'))
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $createdAt = $row['createdAt'];
            $day = $createdAt instanceof \DateTimeInterface ? $createdAt->format('Y-m-d') : (new \DateTimeImmutable($createdAt))->format('Y-m-d');
            if (isset($days[$day])) {
                ++$days[$day];
            }
        }

        return $days;
    }

    /** @return list<SupportTicket> */
    public function findForAdmin(?string $status = null, ?string $query = null, ?string $category = null, ?string $priority = null, ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->distinct()
            ->join('t.requester', 'u')->addSelect('u')
            ->leftJoin('t.messages', 'm');

        $this->applyFilters($qb, $query, $status, $category, $priority, false);
        $this->applySort($qb, $sort);

        /** @var list<SupportTicket> $tickets */
        $tickets = $qb->getQuery()->getResult();

        return $tickets;
    }

    private function applyFilters(QueryBuilder $qb, ?string $query, ?string $status, ?string $category, ?string $priority, bool $requesterOnly): void
    {
        $ticketStatus = $this->statusFromFilter($status);
        if ($ticketStatus !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $ticketStatus);
        }

        $ticketCategory = $this->categoryFromFilter($category);
        if ($ticketCategory !== null) {
            $qb->andWhere('t.category = :category')->setParameter('category', $ticketCategory);
        }

        $ticketPriority = $this->priorityFromFilter($priority);
        if ($ticketPriority !== null) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $ticketPriority);
        }

        if ($query !== null && trim($query) !== '') {
            $searchFields = 'LOWER(t.subject) LIKE :q OR LOWER(t.description) LIKE :q OR LOWER(m.body) LIKE :q';
            if (!$requesterOnly) {
                $searchFields .= ' OR LOWER(u.username) LIKE :q OR LOWER(u.fullName) LIKE :q OR LOWER(u.email) LIKE :q';
            }

            $qb->andWhere('(' . $searchFields . ')')
                ->setParameter('q', '%' . mb_strtolower(trim($query)) . '%');
        }
    }

    private function applySort(QueryBuilder $qb, ?string $sort): void
    {
        match ($sort) {
            'newest' => $qb->orderBy('t.createdAt', 'DESC'),
            'oldest' => $qb->orderBy('t.createdAt', 'ASC'),
            'priority' => $qb
                ->addSelect("CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END AS HIDDEN priorityRank")
                ->orderBy('priorityRank', 'ASC')
                ->addOrderBy('t.updatedAt', 'DESC'),
            default => $qb->orderBy('t.updatedAt', 'DESC'),
        };
    }

    private function statusFromFilter(?string $status): ?TicketStatus
    {
        if ($status === null || trim($status) === '' || $status === 'all') {
            return null;
        }

        return TicketStatus::tryFrom($status);
    }

    private function categoryFromFilter(?string $category): ?TicketCategory
    {
        if ($category === null || trim($category) === '' || $category === 'all') {
            return null;
        }

        return TicketCategory::tryFrom($category);
    }

    private function priorityFromFilter(?string $priority): ?TicketPriority
    {
        if ($priority === null || trim($priority) === '' || $priority === 'all') {
            return null;
        }

        return TicketPriority::tryFrom($priority);
    }

    /**
     * @param list<\BackedEnum> $cases
     * @return array<string, int>
     */
    private function countByEnumField(string $field, array $cases): array
    {
        $counts = [];
        foreach ($cases as $case) {
            $counts[$case->value] = 0;
        }

        /** @var list<array{bucket: \BackedEnum|string, total: int|string}> $rows */
        $rows = $this->createQueryBuilder('t')
            ->select('t.' . $field . ' AS bucket, COUNT(t.id) AS total')
            ->groupBy('t.' . $field)
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $bucket = $row['bucket'];
            $key = $bucket instanceof \BackedEnum ? (string) $bucket->value : (string) $bucket;
            $counts[$key] = (int) $row['total'];
        }

        return $counts;
    }
}

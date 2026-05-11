<?php

declare(strict_types=1);

namespace App\Community\Service;

use App\Community\Entity\BlogArticle;
use App\Community\Entity\CommunityEvent;
use App\Community\Entity\CommunityGroup;
use App\Community\Entity\CommunityPost;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class SearchService
{
    private const MAX_RESULTS_PER_TYPE = 20;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function search(string $query, ?string $type = null, ?string $dateFilter = null, ?User $currentUser = null): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $results = [];

        if ($type === null || $type === 'all' || $type === 'posts') {
            $results['posts'] = $this->searchPosts($query, $dateFilter);
        }
        if ($type === null || $type === 'all' || $type === 'events') {
            $results['events'] = $this->searchEvents($query, $dateFilter);
        }
        if ($type === null || $type === 'all' || $type === 'groups') {
            $results['groups'] = $this->searchGroups($query);
        }
        if ($type === null || $type === 'all' || $type === 'blog') {
            $results['blog'] = $this->searchBlog($query, $dateFilter);
        }
        if (($type === null || $type === 'all' || $type === 'users') && $currentUser !== null) {
            $results['users'] = $this->searchUsers($query, $currentUser);
        }

        return $results;
    }

    /** @return array<string, int> */
    public function countResults(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $param = '%' . $query . '%';

        return [
            'posts' => (int) $this->em->createQueryBuilder()
                ->select('COUNT(p.id)')
                ->from(CommunityPost::class, 'p')
                ->where('p.content LIKE :q')
                ->setParameter('q', $param)
                ->getQuery()->getSingleScalarResult(),

            'events' => (int) $this->em->createQueryBuilder()
                ->select('COUNT(e.id)')
                ->from(CommunityEvent::class, 'e')
                ->where('e.title LIKE :q OR e.description LIKE :q')
                ->setParameter('q', $param)
                ->getQuery()->getSingleScalarResult(),

            'groups' => (int) $this->em->createQueryBuilder()
                ->select('COUNT(g.id)')
                ->from(CommunityGroup::class, 'g')
                ->where('g.name LIKE :q OR g.description LIKE :q')
                ->setParameter('q', $param)
                ->getQuery()->getSingleScalarResult(),

            'blog' => (int) $this->em->createQueryBuilder()
                ->select('COUNT(b.id)')
                ->from(BlogArticle::class, 'b')
                ->where('(b.title LIKE :q OR b.content LIKE :q) AND b.isPublished = true')
                ->setParameter('q', $param)
                ->getQuery()->getSingleScalarResult(),
        ];
    }

    private function searchPosts(string $query, ?string $dateFilter): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p', 'a')
            ->from(CommunityPost::class, 'p')
            ->join('p.author', 'a')
            ->where('p.content LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE);

        $this->applyDateFilter($qb, 'p.createdAt', $dateFilter);

        return array_map(fn ($p) => [
            'type' => 'post',
            'id' => $p->getId(),
            'content' => mb_substr($p->getContent(), 0, 200),
            'author' => $p->getAuthor()->getDisplayName(),
            'created_at' => $p->getCreatedAt()->format('Y-m-d H:i'),
        ], $qb->getQuery()->getResult());
    }

    private function searchEvents(string $query, ?string $dateFilter): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e', 'o')
            ->from(CommunityEvent::class, 'e')
            ->join('e.organizer', 'o')
            ->where('e.title LIKE :q OR e.description LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('e.startDate', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE);

        $this->applyDateFilter($qb, 'e.createdAt', $dateFilter);

        return array_map(fn ($e) => [
            'type' => 'event',
            'id' => $e->getId(),
            'title' => $e->getTitle(),
            'description' => mb_substr($e->getDescription() ?? '', 0, 200),
            'organizer' => $e->getOrganizer()->getDisplayName(),
            'start_date' => $e->getStartDate()?->format('Y-m-d H:i'),
            'created_at' => $e->getCreatedAt()->format('Y-m-d H:i'),
        ], $qb->getQuery()->getResult());
    }

    private function searchGroups(string $query): array
    {
        return array_map(fn ($g) => [
            'type' => 'group',
            'id' => $g->getId(),
            'name' => $g->getName(),
            'description' => mb_substr($g->getDescription() ?? '', 0, 200),
            'creator' => $g->getCreator()->getDisplayName(),
            'member_count' => $g->getMemberCount(),
        ], $this->em->createQueryBuilder()
            ->select('g', 'c')
            ->from(CommunityGroup::class, 'g')
            ->join('g.creator', 'c')
            ->where('g.name LIKE :q OR g.description LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('g.memberCount', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE)
            ->getQuery()
            ->getResult());
    }

    private function searchBlog(string $query, ?string $dateFilter): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('b', 'a')
            ->from(BlogArticle::class, 'b')
            ->join('b.author', 'a')
            ->where('b.title LIKE :q OR b.content LIKE :q')
            ->andWhere('b.isPublished = true')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('b.publishedDate', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE);

        $this->applyDateFilter($qb, 'b.createdAt', $dateFilter);

        return array_map(fn ($b) => [
            'type' => 'blog',
            'id' => $b->getId(),
            'title' => $b->getTitle(),
            'content' => mb_substr($b->getContent() ?? '', 0, 200),
            'author' => $b->getAuthor()->getDisplayName(),
            'published_date' => $b->getPublishedDate()?->format('Y-m-d'),
        ], $qb->getQuery()->getResult());
    }

    private function searchUsers(string $query, User $currentUser): array
    {
        return array_map(fn ($u) => [
            'type' => 'user',
            'id' => $u->getId(),
            'username' => $u->getUsername(),
            'full_name' => $u->getFullName(),
            'display_name' => $u->getDisplayName(),
        ], $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.username LIKE :q OR u.fullName LIKE :q')
            ->andWhere('u.id != :me')
            ->setParameter('q', '%' . $query . '%')
            ->setParameter('me', $currentUser->getId())
            ->orderBy('u.username', 'ASC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE)
            ->getQuery()
            ->getResult());
    }

    private function applyDateFilter(QueryBuilder $qb, string $field, ?string $dateFilter): void
    {
        if ($dateFilter === null || $dateFilter === '') {
            return;
        }

        $now = new \DateTimeImmutable();
        $startDate = match ($dateFilter) {
            'today' => $now->setTime(0, 0),
            'week' => $now->modify('-7 days'),
            'month' => $now->modify('-30 days'),
            'year' => $now->modify('-365 days'),
            default => null,
        };

        if ($startDate !== null) {
            $qb->andWhere("$field >= :startDate")
                ->setParameter('startDate', $startDate);
        }
    }
}

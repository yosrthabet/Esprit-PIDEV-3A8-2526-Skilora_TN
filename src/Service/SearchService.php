<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de recherche globale multi-entités pour la communauté.
 * Recherche dans: Posts, Messages, Événements, Groupes, Blog
 * Porté depuis le module JavaFX community.
 */
class SearchService
{
    private const MAX_RESULTS_PER_TYPE = 20;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Recherche globale dans toutes les entités communautaires.
     *
     * @param string      $query      Le terme de recherche
     * @param string|null $type       Filtrer par type: posts, messages, events, groups, blog (null = tous)
     * @param string|null $dateFilter Filtrer par date: today, week, month, year (null = tout)
     * @return array<string, array>   Résultats groupés par type
     */
    public function search(string $query, ?string $type = null, ?string $dateFilter = null, ?User $currentUser = null): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $results = [];

        if ($type === null || $type === 'posts') {
            $results['posts'] = $this->searchPosts($query, $dateFilter);
        }
        if ($type === null || $type === 'events') {
            $results['events'] = $this->searchEvents($query, $dateFilter);
        }
        if ($type === null || $type === 'groups') {
            $results['groups'] = $this->searchGroups($query);
        }
        if ($type === null || $type === 'blog') {
            $results['blog'] = $this->searchBlog($query, $dateFilter);
        }
        if (($type === null || $type === 'users') && $currentUser !== null) {
            $results['users'] = $this->searchUsers($query, $currentUser);
        }

        return $results;
    }

    private function searchPosts(string $query, ?string $dateFilter): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p', 'a')
            ->from('App\Entity\CommunityPost', 'p')
            ->join('p.author', 'a')
            ->where('p.content LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE);

        $this->applyDateFilter($qb, 'p.createdAt', $dateFilter);

        return $qb->getQuery()->getResult();
    }

    private function searchEvents(string $query, ?string $dateFilter): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e', 'o')
            ->from('App\Entity\CommunityEvent', 'e')
            ->join('e.organizer', 'o')
            ->where('e.title LIKE :q OR e.description LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('e.startDate', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE);

        $this->applyDateFilter($qb, 'e.createdAt', $dateFilter);

        return $qb->getQuery()->getResult();
    }

    private function searchGroups(string $query): array
    {
        return $this->em->createQueryBuilder()
            ->select('g', 'c')
            ->from('App\Entity\CommunityGroup', 'g')
            ->join('g.creator', 'c')
            ->where('g.name LIKE :q OR g.description LIKE :q OR g.category LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('g.memberCount', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE)
            ->getQuery()
            ->getResult();
    }

    private function searchBlog(string $query, ?string $dateFilter): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('b', 'a')
            ->from('App\Entity\BlogArticle', 'b')
            ->join('b.author', 'a')
            ->where('b.title LIKE :q OR b.content LIKE :q OR b.tags LIKE :q')
            ->andWhere('b.isPublished = true')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('b.publishedDate', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE);

        $this->applyDateFilter($qb, 'b.createdAt', $dateFilter);

        return $qb->getQuery()->getResult();
    }

    private function searchUsers(string $query, User $currentUser): array
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\User', 'u')
            ->where('(u.username LIKE :q OR u.full_name LIKE :q)')
            ->andWhere('u.id != :me')
            ->setParameter('q', '%' . $query . '%')
            ->setParameter('me', $currentUser->getId())
            ->orderBy('u.username', 'ASC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE)
            ->getQuery()
            ->getResult();
    }

    private function applyDateFilter($qb, string $field, ?string $dateFilter): void
    {
        if ($dateFilter === null) {
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

    /**
     * Compte total des résultats pour chaque type.
     *
     * @return array<string, int>
     */
    public function countResults(string $query): array
    {
        if (trim($query) === '') {
            return [];
        }

        $counts = [];
        $param = '%' . trim($query) . '%';

        $counts['posts'] = (int) $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from('App\Entity\CommunityPost', 'p')
            ->where('p.content LIKE :q')
            ->setParameter('q', $param)
            ->getQuery()->getSingleScalarResult();

        $counts['events'] = (int) $this->em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from('App\Entity\CommunityEvent', 'e')
            ->where('e.title LIKE :q OR e.description LIKE :q')
            ->setParameter('q', $param)
            ->getQuery()->getSingleScalarResult();

        $counts['groups'] = (int) $this->em->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from('App\Entity\CommunityGroup', 'g')
            ->where('g.name LIKE :q OR g.description LIKE :q')
            ->setParameter('q', $param)
            ->getQuery()->getSingleScalarResult();

        $counts['blog'] = (int) $this->em->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from('App\Entity\BlogArticle', 'b')
            ->where('(b.title LIKE :q OR b.content LIKE :q) AND b.isPublished = true')
            ->setParameter('q', $param)
            ->getQuery()->getSingleScalarResult();

        return $counts;
    }
}

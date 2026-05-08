<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\BlogArticleStatus;
use App\Community\Entity\BlogArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<BlogArticle> */
class BlogArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogArticle::class);
    }

    /** @return list<BlogArticle> */
    public function findPublished(int $limit = 40): array
    {
        /** @var list<BlogArticle> $articles */
        $articles = $this->createQueryBuilder('a')
            ->join('a.author', 'u')->addSelect('u')
            ->where('a.status = :status')
            ->setParameter('status', BlogArticleStatus::PUBLISHED)
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $articles;
    }
}

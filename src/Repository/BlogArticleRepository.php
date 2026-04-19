<?php

namespace App\Repository;

use App\Entity\BlogArticle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlogArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogArticle::class);
    }

    /** @return BlogArticle[] */
    public function findPublished(int $limit = 20): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.author', 'a')
            ->addSelect('a')
            ->where('b.isPublished = true')
            ->orderBy('b.publishedDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return BlogArticle[] */
    public function findByAuthor(User $author): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.author = :author')
            ->setParameter('author', $author)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return BlogArticle[] */
    public function findByCategory(string $category, int $limit = 20): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.author', 'a')
            ->addSelect('a')
            ->where('b.category = :category')
            ->andWhere('b.isPublished = true')
            ->setParameter('category', $category)
            ->orderBy('b.publishedDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPublished(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.isPublished = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return string[] */
    public function findAllCategories(): array
    {
        $results = $this->createQueryBuilder('b')
            ->select('DISTINCT b.category')
            ->where('b.category IS NOT NULL')
            ->orderBy('b.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_filter($results);
    }
}

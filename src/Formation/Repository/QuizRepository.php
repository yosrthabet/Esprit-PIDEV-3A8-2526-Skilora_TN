<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Formation\Entity\Formation;
use App\Formation\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Quiz> */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    /** @return list<Quiz> */
    public function findForFormation(Formation $formation): array
    {
        /** @var list<Quiz> $result */
        $result = $this->createQueryBuilder('q')
            ->where('q.formation = :f')
            ->setParameter('f', $formation)
            ->orderBy('q.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /** @return list<Quiz> */
    public function findPublishedForFormation(Formation $formation): array
    {
        /** @var list<Quiz> $result */
        $result = $this->createQueryBuilder('q')
            ->where('q.formation = :f')
            ->andWhere('q.published = true')
            ->setParameter('f', $formation)
            ->orderBy('q.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Entity\User;
use App\Formation\Entity\Enrollment;
use App\Formation\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Enrollment> */
class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function findOneForUserAndFormation(User $user, Formation $formation): ?Enrollment
    {
        return $this->findOneBy(['user' => $user, 'formation' => $formation]);
    }

    /** @return list<Enrollment> */
    public function findForUser(User $user): array
    {
        /** @var list<Enrollment> $enrollments */
        $enrollments = $this->createQueryBuilder('e')
            ->join('e.formation', 'f')->addSelect('f')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $enrollments;
    }

    /** @return list<Enrollment> */
    public function findForFormation(Formation $formation): array
    {
        /** @var list<Enrollment> $enrollments */
        $enrollments = $this->createQueryBuilder('e')
            ->join('e.user', 'u')->addSelect('u')
            ->leftJoin('e.certificate', 'c')->addSelect('c')
            ->leftJoin('e.lessonProgress', 'p')->addSelect('p')
            ->where('e.formation = :formation')
            ->setParameter('formation', $formation)
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $enrollments;
    }

    public function countStudentsForTrainer(User $trainer): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.user)')
            ->join('e.formation', 'f')
            ->where('f.trainer = :trainer')
            ->setParameter('trainer', $trainer)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForTrainer(User $trainer): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.formation', 'f')
            ->where('f.trainer = :trainer')
            ->setParameter('trainer', $trainer)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

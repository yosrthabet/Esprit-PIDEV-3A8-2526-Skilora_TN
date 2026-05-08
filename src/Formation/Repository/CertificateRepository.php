<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Formation\Entity\Certificate;
use App\Formation\Entity\Enrollment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Certificate> */
class CertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Certificate::class);
    }

    public function findOneForEnrollment(Enrollment $enrollment): ?Certificate
    {
        return $this->findOneBy(['enrollment' => $enrollment]);
    }

    public function findOneByVerificationId(string $verificationId): ?Certificate
    {
        return $this->findOneBy(['verificationId' => $verificationId]);
    }

    public function countForTrainer(User $trainer): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.enrollment', 'e')
            ->join('e.formation', 'f')
            ->where('f.trainer = :trainer')
            ->setParameter('trainer', $trainer)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Certificate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Certificate>
 */
final class CertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Certificate::class);
    }

    /**
     * @return list<Certificate>
     */
    public function findByUserOrdered(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('f')
            ->join('c.formation', 'f')
            ->where('c.user = :u')
            ->setParameter('u', $user)
            ->orderBy('c.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndCertificateId(User $user, int $certificateId): ?Certificate
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :u')
            ->andWhere('c.id = :id')
            ->setParameter('u', $user)
            ->setParameter('id', $certificateId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByVerificationId(string $verificationId): ?Certificate
    {
        return $this->createQueryBuilder('c')
            ->addSelect('u', 'f')
            ->join('c.user', 'u')
            ->join('c.formation', 'f')
            ->where('c.verificationId = :verificationId')
            ->setParameter('verificationId', $verificationId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

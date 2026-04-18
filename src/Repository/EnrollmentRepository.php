<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
final class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function exists(User $user, Formation $formation): bool
    {
        return null !== $this->findOneBy(['user' => $user, 'formation' => $formation]);
    }

    public function findOneByUserAndFormation(User $user, Formation $formation): ?Enrollment
    {
        return $this->findOneBy(['user' => $user, 'formation' => $formation]);
    }

    /**
     * @return list<int>
     */
    public function findEnrolledFormationIds(User $user): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->createQueryBuilder('e')
            ->select('f.id')
            ->join('e.formation', 'f')
            ->where('e.user = :u')
            ->setParameter('u', $user)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(static fn (int|string $id): int => (int) $id, $ids);
    }

    /**
     * @return list<Enrollment>
     */
    public function findByUserOrdered(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->addSelect('f')
            ->join('e.formation', 'f')
            ->where('e.user = :u')
            ->setParameter('u', $user)
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $formationIds
     *
     * @return array<int, int> formationId => enrollment count
     */
    public function countEnrollmentsByFormationIds(array $formationIds): array
    {
        if ([] === $formationIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('e')
            ->select('f.id AS fid, COUNT(e.id) AS c')
            ->join('e.formation', 'f')
            ->where('f.id IN (:ids)')
            ->setParameter('ids', $formationIds)
            ->groupBy('f.id')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['fid']] = (int) $row['c'];
        }

        return $out;
    }
}

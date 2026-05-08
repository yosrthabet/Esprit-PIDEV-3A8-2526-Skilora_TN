<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Formation\Entity\Enrollment;
use App\Formation\Entity\FormationModule;
use App\Formation\Entity\LessonProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<LessonProgress> */
class LessonProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonProgress::class);
    }

    public function findOneForEnrollmentAndModule(Enrollment $enrollment, FormationModule $module): ?LessonProgress
    {
        return $this->findOneBy(['enrollment' => $enrollment, 'module' => $module]);
    }
}

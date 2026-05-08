<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Formation\Entity\Formation;
use App\Formation\Entity\FormationModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FormationModule> */
class FormationModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationModule::class);
    }

    /** @return list<FormationModule> */
    public function findOrderedForFormation(Formation $formation): array
    {
        /** @var list<FormationModule> $modules */
        $modules = $this->createQueryBuilder('m')
            ->where('m.formation = :formation')
            ->setParameter('formation', $formation)
            ->orderBy('m.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $modules;
    }
}

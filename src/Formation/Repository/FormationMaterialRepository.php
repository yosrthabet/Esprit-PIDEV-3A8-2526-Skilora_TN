<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Formation\Entity\FormationMaterial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FormationMaterial> */
class FormationMaterialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationMaterial::class);
    }
}

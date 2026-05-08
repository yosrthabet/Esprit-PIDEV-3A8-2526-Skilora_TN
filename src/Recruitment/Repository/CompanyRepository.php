<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Recruitment\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Company> */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findOneForOwner(User $owner): ?Company
    {
        return $this->findOneBy(['owner' => $owner], ['id' => 'ASC']);
    }
}

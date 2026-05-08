<?php

declare(strict_types=1);

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Entity\SavedJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SavedJob> */
class SavedJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedJob::class);
    }

    public function isSaved(User $user, JobOffer $jobOffer): bool
    {
        return $this->findOneBy(['user' => $user, 'jobOffer' => $jobOffer]) !== null;
    }

    /** @return list<SavedJob> */
    public function findForUser(User $user): array
    {
        /** @var list<SavedJob> $saved */
        $saved = $this->findBy(['user' => $user], ['savedAt' => 'DESC']);

        return $saved;
    }
}

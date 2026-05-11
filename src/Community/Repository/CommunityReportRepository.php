<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\CommunityReportStatus;
use App\Community\Entity\CommunityPost;
use App\Community\Entity\CommunityReport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommunityReport> */
class CommunityReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityReport::class);
    }

    public function findOpenForUserAndPost(User $reporter, CommunityPost $post): ?CommunityReport
    {
        try {
            return $this->findOneBy(['reporter' => $reporter, 'post' => $post, 'status' => CommunityReportStatus::OPEN]);
        } catch (\Throwable) {
            return null;
        }
    }
}

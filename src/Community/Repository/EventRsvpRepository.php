<?php

declare(strict_types=1);

namespace App\Community\Repository;

use App\Community\Entity\CommunityEvent;
use App\Community\Entity\EventRsvp;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EventRsvp> */
class EventRsvpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRsvp::class);
    }

    public function findOneForUserAndEvent(User $user, CommunityEvent $event): ?EventRsvp
    {
        return $this->findOneBy(['user' => $user, 'event' => $event]);
    }
}

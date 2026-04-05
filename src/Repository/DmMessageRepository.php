<?php

namespace App\Repository;

use App\Entity\DmConversation;
use App\Entity\DmMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DmMessage>
 */
class DmMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DmMessage::class);
    }

    /**
     * @return DmMessage[]
     */
    public function findForConversationOrdered(DmConversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :c')
            ->setParameter('c', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

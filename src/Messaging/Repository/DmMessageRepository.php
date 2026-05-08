<?php

declare(strict_types=1);

namespace App\Messaging\Repository;

use App\Messaging\Entity\DmConversation;
use App\Messaging\Entity\DmMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DmMessage> */
class DmMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DmMessage::class);
    }

    /** @return list<DmMessage> */
    public function findForConversation(DmConversation $conversation, int $afterId = 0): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.sender', 's')->addSelect('s')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(80);

        if ($afterId > 0) {
            $qb->andWhere('m.id > :afterId')->setParameter('afterId', $afterId);
        }

        /** @var list<DmMessage> $messages */
        $messages = $qb->getQuery()->getResult();

        return $messages;
    }
}

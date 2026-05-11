<?php

declare(strict_types=1);

namespace App\Messaging\Service;

use App\Entity\User;
use App\Messaging\Entity\DmConversation;
use App\Messaging\Repository\DmConversationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class JobConversationService
{
    public function __construct(
        private readonly DmConversationRepository $conversationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getOrCreateForContext(User $a, User $b, string $contextLabel): DmConversation
    {
        $existing = $this->conversationRepository->findOneDirectConversation($a, $b);
        if ($existing !== null) {
            if ($existing->getSubject() === null || $existing->getSubject() === '') {
                $existing->setSubject($contextLabel);
                $this->entityManager->flush();
            }

            return $existing;
        }

        $conversation = new DmConversation();
        $conversation->setSubject($contextLabel);
        $conversation->addParticipant($a);
        $conversation->addParticipant($b);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $conversation;
    }
}

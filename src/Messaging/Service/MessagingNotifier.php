<?php

declare(strict_types=1);

namespace App\Messaging\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Messaging\Entity\DmConversation;
use Doctrine\ORM\EntityManagerInterface;

class MessagingNotifier
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function notifyRecipient(DmConversation $conversation, User $sender, User $recipient, string $body): void
    {
        if ($conversation->getId() === null) {
            return;
        }

        $this->entityManager->persist((new Notification())
            ->setUser($recipient)
            ->setType('dm.message')
            ->setTitle('New message')
            ->setMessage($sender->getDisplayName() . ': ' . mb_substr($body, 0, 140))
            ->setIcon('mail')
            ->setReferenceType('dm_conversation')
            ->setReferenceId($conversation->getId()));
    }
}

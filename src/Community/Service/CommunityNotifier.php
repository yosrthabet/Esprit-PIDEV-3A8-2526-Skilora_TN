<?php

declare(strict_types=1);

namespace App\Community\Service;

use App\Community\Entity\CommunityPost;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CommunityNotifier
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function notifyPostAuthor(CommunityPost $post, User $actor, string $type, string $title, string $message): void
    {
        if ($post->getId() === null || $post->getAuthor()->getId() === $actor->getId()) {
            return;
        }

        $this->entityManager->persist((new Notification())
            ->setUser($post->getAuthor())
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setIcon('message-circle')
            ->setReferenceType('community_post')
            ->setReferenceId($post->getId()));
    }
}

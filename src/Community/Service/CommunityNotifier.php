<?php

declare(strict_types=1);

namespace App\Community\Service;

use App\Community\Entity\CommunityGroup;
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
            ->setIcon('message-square')
            ->setReferenceType('community_post')
            ->setReferenceId($post->getId()));
    }

    public function notifyPostLiked(CommunityPost $post, User $liker): void
    {
        $this->notifyPostAuthor($post, $liker, 'community_like', 'New like', $liker->getDisplayName() . ' liked your post.');
    }

    public function notifyPostCommented(CommunityPost $post, User $commenter): void
    {
        $this->notifyPostAuthor($post, $commenter, 'community_comment', 'New comment', $commenter->getDisplayName() . ' commented on your post.');
    }

    public function notifyGroupJoin(CommunityGroup $group, User $joiner): void
    {
        $owner = $group->getOwner();
        if ($owner->getId() === $joiner->getId() || $group->getId() === null) {
            return;
        }
        $this->entityManager->persist((new Notification())
            ->setUser($owner)
            ->setType('community_group_join')
            ->setTitle('New member')
            ->setMessage($joiner->getDisplayName() . ' joined your group "' . $group->getName() . '".')
            ->setIcon('users')
            ->setReferenceType('community_group')
            ->setReferenceId($group->getId()));
    }

    public function notifyMention(CommunityPost $post, User $mentioned, User $mentioner): void
    {
        if ($mentioned->getId() === $mentioner->getId() || $post->getId() === null) {
            return;
        }
        $this->entityManager->persist((new Notification())
            ->setUser($mentioned)
            ->setType('community_mention')
            ->setTitle('You were mentioned')
            ->setMessage($mentioner->getDisplayName() . ' mentioned you in a post.')
            ->setIcon('at-sign')
            ->setReferenceType('community_post')
            ->setReferenceId($post->getId()));
    }
}

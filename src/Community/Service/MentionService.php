<?php

declare(strict_types=1);

namespace App\Community\Service;

use App\Community\Entity\CommunityPost;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class MentionService
{
    private const MENTION_PATTERN = '/@(\w+(?:_\w+)*)/';
    private const MAX_AUTOCOMPLETE = 8;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return string[] */
    public function extractMentions(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        preg_match_all(self::MENTION_PATTERN, $text, $matches);

        return array_unique($matches[1] ?? []);
    }

    /** @return User[] */
    public function resolveUsers(array $usernames): array
    {
        if ($usernames === []) {
            return [];
        }

        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.username IN (:usernames)')
            ->setParameter('usernames', $usernames)
            ->getQuery()
            ->getResult();
    }

    /** @return User[] */
    public function processMentions(string $text, User $author, ?CommunityPost $post = null): array
    {
        $usernames = $this->extractMentions($text);
        if ($usernames === []) {
            return [];
        }

        $users = $this->resolveUsers($usernames);

        foreach ($users as $user) {
            if ($user->getId() === $author->getId()) {
                continue;
            }

            $notification = (new Notification())
                ->setUser($user)
                ->setType('community_mention')
                ->setTitle('You were mentioned')
                ->setMessage(sprintf('%s mentioned you in a post.', $author->getDisplayName()))
                ->setIcon('at-sign')
                ->setReferenceType('community_post')
                ->setReferenceId($post?->getId());

            $this->em->persist($notification);
        }

        if ($users !== []) {
            $this->em->flush();
        }

        return $users;
    }

    /** @return list<array{id: int, username: string, fullName: string}> */
    public function autocomplete(string $prefix, ?User $exclude = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u.id', 'u.username', 'u.full_name as fullName')
            ->from(User::class, 'u')
            ->where('u.username LIKE :prefix OR u.full_name LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('u.username', 'ASC')
            ->setMaxResults(self::MAX_AUTOCOMPLETE);

        if ($exclude !== null) {
            $qb->andWhere('u.id != :excludeId')
                ->setParameter('excludeId', $exclude->getId());
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function renderMentions(string $text): string
    {
        return preg_replace(
            self::MENTION_PATTERN,
            '<span class="mention text-primary font-semibold cursor-pointer hover:underline">@$1</span>',
            $text
        ) ?? $text;
    }
}

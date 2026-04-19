<?php

namespace App\Service;

use App\Entity\CommunityNotification;
use App\Entity\User;
use App\Repository\CommunityNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de mentions (@mention) dans les publications et messages.
 * Détecte les @username dans le texte, résout les utilisateurs et crée des notifications.
 * Porté depuis le module JavaFX community.
 */
class MentionService
{
    private const MENTION_PATTERN = '/@(\w+(?:_\w+)*)/';
    private const MAX_AUTOCOMPLETE = 8;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Extrait les mentions @username d'un texte.
     *
     * @return string[] Liste des usernames mentionnés
     */
    public function extractMentions(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        preg_match_all(self::MENTION_PATTERN, $text, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Résout les usernames en objets User.
     *
     * @param string[] $usernames
     * @return User[]
     */
    public function resolveUsers(array $usernames): array
    {
        if (empty($usernames)) {
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

    /**
     * Traite les mentions dans un texte : extrait, résout et crée les notifications.
     *
     * @return User[] Les utilisateurs mentionnés trouvés
     */
    public function processMentions(string $text, User $author, string $referenceType = 'post', ?int $referenceId = null): array
    {
        $mentionedUsernames = $this->extractMentions($text);
        if (empty($mentionedUsernames)) {
            return [];
        }

        $users = $this->resolveUsers($mentionedUsernames);

        foreach ($users as $user) {
            // Ne pas notifier l'auteur lui-même
            if ($user->getId() === $author->getId()) {
                continue;
            }

            $notification = new CommunityNotification();
            $notification->setUser($user);
            $notification->setType('MENTION');
            $notification->setTitle('Nouvelle mention');
            $notification->setMessage(
                sprintf('%s vous a mentionné dans un(e) %s', $author->getFullName(), $referenceType)
            );
            $notification->setIcon('💬');
            $notification->setReferenceType($referenceType);
            $notification->setReferenceId($referenceId);

            $this->em->persist($notification);
        }

        if (!empty($users)) {
            $this->em->flush();
            $this->logger->info('Mention notifications created', ['count' => count($users)]);
        }

        return $users;
    }

    /**
     * Autocomplétion de mentions — recherche d'utilisateurs par préfixe.
     *
     * @return array<array{id: int, username: string, fullName: string}>
     */
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

    /**
     * Transforme le texte en remplaçant les @mentions par des liens HTML.
     */
    public function renderMentions(string $text): string
    {
        return preg_replace(
            self::MENTION_PATTERN,
            '<span class="mention text-primary font-semibold cursor-pointer hover:underline">@$1</span>',
            $text
        ) ?? $text;
    }
}

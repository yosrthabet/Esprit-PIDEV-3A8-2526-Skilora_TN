<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CommunityNotificationRepository;
use App\Repository\CommunityPostRepository;
use App\Repository\BlogArticleRepository;
use App\Repository\CommunityEventRepository;
use App\Repository\CommunityGroupRepository;
use App\Repository\MemberInvitationRepository;

/**
 * Service de statistiques du dashboard communauté.
 * Fournit des stats agrégées selon le rôle de l'utilisateur.
 * Porté depuis le module JavaFX community.
 */
class DashboardStatsService
{
    public function __construct(
        private readonly CommunityPostRepository $postRepo,
        private readonly BlogArticleRepository $blogRepo,
        private readonly CommunityEventRepository $eventRepo,
        private readonly CommunityGroupRepository $groupRepo,
        private readonly MemberInvitationRepository $invitationRepo,
        private readonly CommunityNotificationRepository $notificationRepo,
    ) {
    }

    /**
     * Retourne toutes les statistiques communautaires pour un utilisateur.
     *
     * @return array<string, int|string>
     */
    public function getStats(User $user): array
    {
        $friends = $this->invitationRepo->findFriendsFor($user);

        return [
            'totalPosts' => $this->postRepo->countAll(),
            'myPosts' => $this->postRepo->countByAuthor($user),
            'totalEvents' => $this->eventRepo->countUpcoming(),
            'totalGroups' => $this->groupRepo->countAll(),
            'totalBlogArticles' => $this->blogRepo->countPublished(),
            'friendsCount' => count($friends),
            'pendingInvitations' => $this->invitationRepo->countPendingFor($user),
            'unreadNotifications' => $this->notificationRepo->countUnreadFor($user),
        ];
    }
}

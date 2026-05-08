<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\Notification\NotificationUrlResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications', name: 'app_notifications_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $em,
        private readonly NotificationUrlResolver $notificationUrlResolver,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findRecentForUser($user, 50);
        $notificationUrls = [];

        foreach ($notifications as $notification) {
            if ($notification->getId() !== null) {
                $notificationUrls[$notification->getId()] = $this->notificationUrlResolver->resolve(
                    $notification->getReferenceType(),
                    $notification->getReferenceId(),
                    $user,
                );
            }
        }

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
            'notification_urls' => $notificationUrls,
        ]);
    }

    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'count' => $this->notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[Route('/recent', name: 'recent', methods: ['GET'])]
    public function recent(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findRecentForUser($user, 8);

        return new JsonResponse([
            'notifications' => array_map(fn ($notification): array => [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'icon' => $notification->getIcon() ?? '🔔',
                'read' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('M d, H:i'),
                'url' => $this->notificationUrlResolver->resolve(
                    $notification->getReferenceType(),
                    $notification->getReferenceId(),
                    $user,
                ),
            ], $notifications),
        ]);
    }

    #[Route('/mark-all-read', name: 'mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('mark_notifications_read', $request->request->getString('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Invalid token'], 403);
            }
            $this->addFlash('error', 'Session expirée. Réessayez.');
            return $this->redirectToRoute('app_notifications_index');
        }

        /** @var User $user */
        $user = $this->getUser();
        $count = $this->notificationRepository->markAllReadForUser($user);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['marked' => $count]);
        }

        $this->addFlash('success', $count . ' notification(s) marquée(s) comme lue(s).');
        return $this->redirectToRoute('app_notifications_index');
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markRead(int $id, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('notif_read_' . $id, $request->request->getString('_token'))) {
            return new JsonResponse(['error' => 'Invalid token'], 403);
        }

        /** @var User $user */
        $user = $this->getUser();
        $notification = $this->notificationRepository->find($id);

        if (!$notification || $notification->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $notification->setRead(true);
        $this->em->flush();

        return new JsonResponse(['ok' => true]);
    }
}

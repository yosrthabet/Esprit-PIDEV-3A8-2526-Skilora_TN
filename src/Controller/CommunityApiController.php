<?php

namespace App\Controller;

use App\Entity\CommunityNotification;
use App\Entity\CommunityPost;
use App\Entity\PostComment;
use App\Entity\PostLike;
use App\Entity\User;
use App\Repository\CommunityNotificationRepository;
use App\Repository\PostCommentRepository;
use App\Repository\PostLikeRepository;
use App\Service\MentionService;
use App\Service\SearchService;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/community')]
#[IsGranted('ROLE_USER')]
class CommunityApiController extends AbstractController
{
    // ── Like toggle ─────────────────────────────────────────
    #[Route('/posts/{id}/like', name: 'app_community_post_like', methods: ['POST'])]
    public function toggleLike(
        Request $request,
        CommunityPost $post,
        EntityManagerInterface $em,
        PostLikeRepository $likeRepo,
    ): JsonResponse {
        $me = $this->requireUser();

        if (!$this->isCsrfTokenValid('like_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            return $this->json(['error' => 'Token CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        $existing = $likeRepo->findByPostAndUser($post, $me);

        if ($existing) {
            $em->remove($existing);
            $post->setLikesCount(max(0, $post->getLikesCount() - 1));
            $liked = false;
        } else {
            $like = new PostLike();
            $like->setPost($post);
            $like->setUser($me);
            $em->persist($like);
            $post->setLikesCount($post->getLikesCount() + 1);
            $liked = true;
        }

        $em->flush();

        return $this->json([
            'liked' => $liked,
            'likesCount' => $post->getLikesCount(),
        ]);
    }

    // ── Add comment ─────────────────────────────────────────
    #[Route('/posts/{id}/comment', name: 'app_community_post_comment', methods: ['POST'])]
    public function addComment(
        Request $request,
        CommunityPost $post,
        EntityManagerInterface $em,
        MentionService $mentionService,
    ): JsonResponse {
        $me = $this->requireUser();

        if (!$this->isCsrfTokenValid('comment_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            return $this->json(['error' => 'Token CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        $content = trim((string) $request->request->get('content', ''));
        if ($content === '' || mb_strlen($content) > 2000) {
            return $this->json(['error' => 'Le commentaire doit faire entre 1 et 2000 caractères.'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new PostComment();
        $comment->setPost($post);
        $comment->setAuthor($me);
        $comment->setContent($content);
        $em->persist($comment);

        $post->setCommentsCount($post->getCommentsCount() + 1);

        // Process @mentions
        $mentionService->processMentions($content, $me, 'COMMENT', $post->getId());

        $em->flush();

        return $this->json([
            'success' => true,
            'comment' => [
                'id' => $comment->getId(),
                'author' => $me->getFullName() ?: $me->getUserIdentifier(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
            ],
            'commentsCount' => $post->getCommentsCount(),
        ]);
    }

    // ── Delete comment ──────────────────────────────────────
    #[Route('/comments/{id}/delete', name: 'app_community_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Request $request,
        PostComment $comment,
        EntityManagerInterface $em,
    ): JsonResponse {
        $me = $this->requireUser();

        if ($comment->getAuthor()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->isCsrfTokenValid('delete_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            return $this->json(['error' => 'Token CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        $post = $comment->getPost();
        $post->setCommentsCount(max(0, $post->getCommentsCount() - 1));

        $em->remove($comment);
        $em->flush();

        return $this->json(['success' => true, 'commentsCount' => $post->getCommentsCount()]);
    }

    // ── Translate post ──────────────────────────────────────
    #[Route('/posts/{id}/translate', name: 'app_community_post_translate', methods: ['POST'])]
    public function translatePost(
        Request $request,
        CommunityPost $post,
        TranslationService $translationService,
    ): JsonResponse {
        $targetLang = (string) $request->request->get('lang', 'fr');

        if (!in_array($targetLang, ['fr', 'en', 'ar', 'es', 'de'], true)) {
            return $this->json(['error' => 'Langue non supportée'], Response::HTTP_BAD_REQUEST);
        }

        $sourceLang = $translationService->detectLanguage($post->getContent());
        $translated = $translationService->translate($post->getContent(), $sourceLang, $targetLang);

        return $this->json([
            'translatedContent' => $translated,
            'detectedLanguage' => $sourceLang,
            'targetLanguage' => $targetLang,
        ]);
    }

    // ── Global search ───────────────────────────────────────
    #[Route('/search', name: 'app_community_search', methods: ['GET'])]
    public function search(Request $request, SearchService $searchService): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', 'all');
        $period = (string) $request->query->get('period', '');

        if ($query === '') {
            return $this->json(['results' => [], 'counts' => []]);
        }

        $results = $searchService->search($query, $type, $period);
        $counts = $searchService->countResults($query);

        return $this->json([
            'results' => $results,
            'counts' => $counts,
        ]);
    }

    // ── Mention autocomplete ────────────────────────────────
    #[Route('/mentions/autocomplete', name: 'app_community_mention_autocomplete', methods: ['GET'])]
    public function mentionAutocomplete(Request $request, MentionService $mentionService): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        return $this->json($mentionService->autocomplete($query));
    }

    // ── Notifications ───────────────────────────────────────
    #[Route('/notifications', name: 'app_community_notifications', methods: ['GET'])]
    public function notifications(CommunityNotificationRepository $notifRepo): Response
    {
        $me = $this->requireUser();

        return $this->render('community/notifications/index.html.twig', [
            'notifications' => $notifRepo->findByUserRecent($me),
            'unreadCount' => $notifRepo->countUnread($me),
        ]);
    }

    #[Route('/notifications/read/{id}', name: 'app_community_notification_read', methods: ['POST'])]
    public function markNotificationRead(
        CommunityNotification $notification,
        EntityManagerInterface $em,
    ): JsonResponse {
        $me = $this->requireUser();

        if ($notification->getUser()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $notification->setIsRead(true);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/notifications/read-all', name: 'app_community_notifications_read_all', methods: ['POST'])]
    public function markAllRead(
        CommunityNotificationRepository $notifRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $me = $this->requireUser();

        foreach ($notifRepo->findUnreadByUser($me) as $notif) {
            $notif->setIsRead(true);
        }
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/notifications/unread-count', name: 'app_community_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(CommunityNotificationRepository $notifRepo): JsonResponse
    {
        return $this->json([
            'count' => $notifRepo->countUnread($this->requireUser()),
        ]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }
}

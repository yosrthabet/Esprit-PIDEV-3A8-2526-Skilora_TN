<?php

declare(strict_types=1);

namespace App\Community\Controller;

use App\Community\CommunityPostStatus;
use App\Community\CommunityPostVisibility;
use App\Community\CommunityReactionType;
use App\Community\Entity\CommunityBookmark;
use App\Community\Entity\CommunityComment;
use App\Community\Entity\CommunityLike;
use App\Community\Entity\CommunityPost;
use App\Community\Entity\CommunityReaction;
use App\Community\Entity\CommunityReport;
use App\Community\Entity\CommunityShare;
use App\Community\Repository\CommunityBookmarkRepository;
use App\Community\Repository\CommunityCommentRepository;
use App\Community\Repository\CommunityLikeRepository;
use App\Community\Repository\CommunityPostRepository;
use App\Community\Repository\CommunityReactionRepository;
use App\Community\Repository\CommunityReportRepository;
use App\Community\Repository\CommunityShareRepository;
use App\Community\Repository\MemberInvitationRepository;
use App\Community\Service\CommunityAiAssistant;
use App\Community\Service\CommunityNotifier;
use App\Community\Service\ContentModerationService;
use App\Community\Service\MentionService;
use App\Community\Service\SearchService;
use App\Community\Service\TranslationService;
use App\Controller\AppController;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommunityController extends AppController
{
    public function __construct(
        private readonly CommunityPostRepository $postRepository,
        private readonly CommunityLikeRepository $likeRepository,
        private readonly CommunityReactionRepository $reactionRepository,
        private readonly CommunityBookmarkRepository $bookmarkRepository,
        private readonly CommunityCommentRepository $commentRepository,
        private readonly CommunityReportRepository $reportRepository,
        private readonly CommunityShareRepository $shareRepository,
        private readonly MemberInvitationRepository $invitationRepository,
        private readonly CommunityNotifier $notifier,
        private readonly ContentModerationService $moderationService,
        private readonly CommunityAiAssistant $aiAssistant,
        private readonly TranslationService $translationService,
        private readonly MentionService $mentionService,
        private readonly SearchService $searchService,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/community', name: 'app_community', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user = $this->getAppUser();
        $connections = $this->invitationRepository->findFriendsFor($user);
        $posts = $this->postRepository->findVisibleForUser($user, $connections, 30);

        return $this->renderFeed($posts, 'latest');
    }

    #[Route('/community/for-you', name: 'app_community_for_you', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function forYou(): Response
    {
        $user = $this->getAppUser();
        $connections = $this->invitationRepository->findFriendsFor($user);
        $posts = $this->postRepository->findVisibleForUser($user, $connections, 80);
        $counts = $this->reactionRepository->countByTypeForPosts($posts);

        usort($posts, static function (CommunityPost $a, CommunityPost $b) use ($counts): int {
            return self::engagementScore($b, $counts[$b->getId() ?? 0] ?? []) <=> self::engagementScore($a, $counts[$a->getId() ?? 0] ?? []);
        });

        return $this->renderFeed(array_slice($posts, 0, 30), 'for_you');
    }

    #[Route('/community/following', name: 'app_community_following', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function following(): Response
    {
        $user = $this->getAppUser();

        return $this->renderFeed($this->postRepository->findForConnections($user, $this->invitationRepository->findFriendsFor($user), 30), 'following');
    }

    #[Route('/community/saved', name: 'app_community_saved', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function saved(): Response
    {
        return $this->renderFeed($this->bookmarkRepository->findPostsForUser($this->getAppUser()), 'saved');
    }

    #[Route('/community/posts/new', name: 'app_community_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request): Response
    {
        $user = $this->getAppUser();
        $this->denyAdminSocialAction();
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('community_post_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $post = $this->createPostFromRequest($request, $user);
            if ($post->getContent() === '') {
                $this->addFlash('error', 'Post content is required.');

                return $this->redirectToRoute('app_community_post_new');
            }

            if (!$this->moderateOrFlash($post->getContent())) {
                return $this->redirectToRoute('app_community_post_new');
            }

            if ($post->getMediaPath() !== null && !$this->moderateImageOrFlash($post->getMediaPath())) {
                return $this->redirectToRoute('app_community_post_new');
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
        }

        return $this->render('community/feed/new.html.twig');
    }

    #[Route('/community/posts/{id}/edit', name: 'app_community_post_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, CommunityPost $post): Response
    {
        $user = $this->getAppUser();
        if ($post->getAuthor()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('community_post_edit_' . $post->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $content = trim($request->request->getString('content'));
            if ($content === '') {
                $this->addFlash('error', 'Post content is required.');

                return $this->redirectToRoute('app_community_post_edit', ['id' => $post->getId()]);
            }
            if (!$this->moderateOrFlash($content)) {
                return $this->redirectToRoute('app_community_post_edit', ['id' => $post->getId()]);
            }
            $post->setContent($content);
            $post->setVisibility(CommunityPostVisibility::tryFrom($request->request->getString('visibility')) ?? $post->getVisibility());
            $mediaPath = $this->storeMedia($request->files->get('media'));
            if ($mediaPath !== null) {
                $post->setMediaPath($mediaPath);
            }
            $this->entityManager->flush();

            return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
        }

        return $this->render('community/feed/edit.html.twig', ['post' => $post]);
    }

    #[Route('/community/posts/{id}', name: 'app_community_post_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(CommunityPost $post): Response
    {
        if (!$post->isVisible() && !$this->getAppUser()->isAdmin() && $post->getAuthor()->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('community/feed/show.html.twig', [
            'post' => $post,
            'comments' => $this->commentRepository->findTopLevelForPost($post),
            'liked' => $this->likeRepository->findOneForUserAndPost($this->getAppUser(), $post) !== null,
            'reaction_counts' => $this->reactionRepository->countByTypeForPost($post),
            'my_reaction' => $this->reactionRepository->findOneForUserAndPost($this->getAppUser(), $post)?->getType()->value,
            'bookmarked' => $this->bookmarkRepository->findOneForUserAndPost($this->getAppUser(), $post) !== null,
            'reaction_types' => CommunityReactionType::cases(),
        ]);
    }

    #[Route('/community/posts/{id}/comments', name: 'app_community_post_comment', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function comment(Request $request, CommunityPost $post): Response
    {
        $user = $this->getAppUser();
        $this->denyAdminSocialAction();
        $this->assertVisiblePost($post);
        if (!$this->isCsrfTokenValid('community_comment_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $content = trim($request->request->getString('content'));
        if ($content !== '') {
            if (!$this->moderateOrFlash($content)) {
                return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
            }
            $parent = null;
            $parentId = $request->request->getInt('parent_id');
            if ($parentId > 0) {
                $parent = $this->commentRepository->find($parentId);
                if (!$parent instanceof CommunityComment || $parent->getPost()->getId() !== $post->getId()) {
                    throw $this->createNotFoundException('Parent comment not found.');
                }
            }
            $comment = (new CommunityComment())
                ->setPost($post)
                ->setAuthor($user)
                ->setContent($content)
                ->setParent($parent);
            $post->incrementComments();
            $this->entityManager->persist($comment);
            $this->notifier->notifyPostAuthor($post, $user, 'community.comment_replied', 'New comment on your post', $content);
            $this->entityManager->flush();

            if ($this->wantsJson($request)) {
                return $this->json([
                    'success' => true,
                    'commentsCount' => $post->getCommentsCount(),
                    'comment' => [
                        'id' => $comment->getId(),
                        'author' => $user->getDisplayName(),
                        'content' => $comment->getContent(),
                        'createdAt' => $comment->getCreatedAt()->format('M d, H:i'),
                    ],
                ]);
            }
        }

        return $this->redirectBack($request, $post);
    }

    #[Route('/community/posts/{id}/like', name: 'app_community_post_like', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function like(Request $request, CommunityPost $post): Response
    {
        return $this->react($request, $post, CommunityReactionType::LIKE->value);
    }

    #[Route('/community/posts/{id}/react/{type}', name: 'app_community_post_react', methods: ['POST'], requirements: ['id' => '\d+', 'type' => '[a-z_]+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function react(Request $request, CommunityPost $post, string $type): Response
    {
        $user = $this->getAppUser();
        $this->denyAdminSocialAction();
        $this->assertVisiblePost($post);
        if (!$this->isCsrfTokenValid('community_react_' . $post->getId(), $request->request->getString('_token'))
            && !$this->isCsrfTokenValid('community_like_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$this->hasTable('community_feed_reactions')) {
            return $this->legacyLike($request, $post, $user);
        }

        $reactionType = CommunityReactionType::tryFrom($type) ?? CommunityReactionType::LIKE;
        $reaction = $this->reactionRepository->findOneForUserAndPost($user, $post);
        $active = true;
        if ($reaction === null) {
            $this->entityManager->persist((new CommunityReaction())->setUser($user)->setPost($post)->setType($reactionType));
            $post->incrementLikes();
            $this->notifier->notifyPostAuthor($post, $user, 'community.post_reacted', 'New reaction', $user->getDisplayName() . ' reacted to your community post.');
        } elseif ($reaction->getType() === $reactionType) {
            $this->entityManager->remove($reaction);
            $post->decrementLikes();
            $active = false;
        } else {
            $reaction->setType($reactionType);
        }
        $this->entityManager->flush();

        if ($this->wantsJson($request)) {
            return $this->json([
                'active' => $active,
                'type' => $reactionType->value,
                'counts' => $this->reactionRepository->countByTypeForPost($post),
                'total' => $post->getLikesCount(),
            ]);
        }

        return $this->redirectBack($request, $post);
    }

    #[Route('/community/posts/{id}/bookmark', name: 'app_community_post_bookmark', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function bookmark(Request $request, CommunityPost $post): Response
    {
        $user = $this->getAppUser();
        $this->denyAdminSocialAction();
        $this->assertVisiblePost($post);
        if (!$this->isCsrfTokenValid('community_bookmark_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if (!$this->hasTable('community_feed_bookmarks')) {
            $this->addFlash('info', 'Saved posts will be available after the community schema upgrade is applied.');

            return $this->wantsJson($request) ? $this->json(['active' => false, 'available' => false]) : $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
        }
        $bookmark = $this->bookmarkRepository->findOneForUserAndPost($user, $post);
        $active = $bookmark === null;
        if ($bookmark === null) {
            $this->entityManager->persist((new CommunityBookmark())->setUser($user)->setPost($post));
        } else {
            $this->entityManager->remove($bookmark);
        }
        $this->entityManager->flush();

        return $this->wantsJson($request) ? $this->json(['active' => $active]) : $this->redirectBack($request, $post);
    }

    #[Route('/community/posts/{id}/share', name: 'app_community_post_share', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function share(Request $request, CommunityPost $post): Response
    {
        $user = $this->getAppUser();
        $this->denyAdminSocialAction();
        $this->assertVisiblePost($post);
        if (!$this->isCsrfTokenValid('community_share_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $alreadyShared = false;
        if ($this->hasTable('community_feed_shares')) {
            $existing = $this->shareRepository->findOneForUserAndPost($user, $post);
            if ($existing !== null) {
                $alreadyShared = true;
            } else {
                $this->entityManager->persist((new CommunityShare())->setUser($user)->setPost($post));
                $post->incrementShares();
                $this->entityManager->flush();
            }
        } else {
            $post->incrementShares();
            $this->entityManager->flush();
        }

        if ($this->wantsJson($request)) {
            return $this->json(['shares' => $post->getSharesCount(), 'shared' => !$alreadyShared, 'alreadyShared' => $alreadyShared]);
        }

        return $this->redirectBack($request, $post);
    }

    #[Route('/community/comments/{id}/edit', name: 'app_community_comment_edit', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function commentEdit(Request $request, CommunityComment $comment): Response
    {
        $user = $this->getAppUser();
        if ($comment->getAuthor()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only edit your own comments.');
        }
        if (!$this->isCsrfTokenValid('community_comment_edit_' . $comment->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $content = trim($request->request->getString('content'));
        if ($content === '') {
            if ($this->wantsJson($request)) {
                return $this->json(['error' => 'Content cannot be empty.'], 422);
            }
            return $this->redirectBack($request, $comment->getPost());
        }
        if (!$this->moderateOrFlash($content)) {
            if ($this->wantsJson($request)) {
                return $this->json(['error' => 'Content flagged by moderation.'], 422);
            }
            return $this->redirectBack($request, $comment->getPost());
        }
        $comment->setContent($content);
        $comment->markEdited();
        $this->entityManager->flush();

        if ($this->wantsJson($request)) {
            return $this->json([
                'success' => true,
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'updatedAt' => $comment->getUpdatedAt()?->format('M d, H:i'),
            ]);
        }

        return $this->redirectBack($request, $comment->getPost());
    }

    #[Route('/community/comments/{id}/delete', name: 'app_community_comment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function commentDelete(Request $request, CommunityComment $comment): Response
    {
        $user = $this->getAppUser();
        $post = $comment->getPost();
        if ($comment->getAuthor()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only delete your own comments.');
        }
        if (!$this->isCsrfTokenValid('community_comment_delete_' . $comment->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $post->decrementComments();
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        if ($this->wantsJson($request)) {
            return $this->json(['success' => true, 'commentsCount' => $post->getCommentsCount()]);
        }

        return $this->redirectBack($request, $post);
    }

    #[Route('/community/posts/{id}/report', name: 'app_community_post_report', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function report(Request $request, CommunityPost $post): Response
    {
        $user = $this->getAppUser();
        $this->denyAdminSocialAction();
        $this->assertVisiblePost($post);
        if (!$this->isCsrfTokenValid('community_report_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if (!$this->hasTable('community_feed_reports')) {
            $this->addFlash('info', 'Reports will be stored after the community schema upgrade is applied.');

            return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
        }
        if ($this->reportRepository->findOpenForUserAndPost($user, $post) === null) {
            $this->entityManager->persist((new CommunityReport())
                ->setPost($post)
                ->setReporter($user)
                ->setReason($request->request->getString('reason') ?: 'Inappropriate content')
                ->setDetails($request->request->getString('details')));
            $post->incrementReports();
            $this->entityManager->flush();
        }
        $this->addFlash('success', 'Thanks. The post has been sent to moderation.');

        return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
    }

    #[Route('/community/posts/{id}/delete', name: 'app_community_post_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(Request $request, CommunityPost $post): Response
    {
        if (!$this->getAppUser()->isAdmin() && $post->getAuthor()->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('community_delete_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $post->setStatus(CommunityPostStatus::DELETED);
        $this->entityManager->flush();

        return $this->redirectToRoute($this->getAppUser()->isAdmin() ? 'app_admin_community' : 'app_community');
    }

    #[Route('/admin/community', name: 'app_admin_community', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        return $this->render('community/admin/index.html.twig', [
            'posts' => $this->postRepository->findForModeration(),
        ]);
    }

    #[Route('/admin/community/posts/{id}/approve', name: 'app_admin_community_post_approve', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminApprove(Request $request, CommunityPost $post): Response
    {
        $this->assertAdminToken($request, $post, 'approve');
        $post->setStatus(CommunityPostStatus::PUBLISHED)->setModerationReason(null);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_community');
    }

    #[Route('/admin/community/posts/{id}/reject', name: 'app_admin_community_post_reject', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminReject(Request $request, CommunityPost $post): Response
    {
        $this->assertAdminToken($request, $post, 'reject');
        $post
            ->setStatus(CommunityPostStatus::REJECTED)
            ->setModerationReason(trim($request->request->getString('reason')) ?: 'Rejected by moderation.');
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_community');
    }

    /** @return list<int> */
    private function likedPostIds(User $user): array
    {
        $ids = [];
        foreach ($this->likeRepository->findBy(['user' => $user]) as $like) {
            $postId = $like->getPost()->getId();
            if ($postId !== null) {
                $ids[] = $postId;
            }
        }

        return $ids;
    }

    private function assertVisiblePost(CommunityPost $post): void
    {
        if (!$post->isVisible()) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('/community/search', name: 'app_community_search', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function search(Request $request): Response
    {
        $q = trim($request->query->getString('q'));
        $posts = $q !== '' ? $this->postRepository->search($q) : [];

        return $this->renderFeed($posts, 'search', ['search_query' => $q]);
    }

    #[Route('/community/ai/tone-check', name: 'app_community_ai_tone_check', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function toneCheck(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('community_ai', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->json($this->aiAssistant->analyzeTone($request->request->getString('content')));
    }

    #[Route('/community/ai/improve', name: 'app_community_ai_improve', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function improveDraft(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('community_ai', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->json(['content' => $this->aiAssistant->improve($request->request->getString('content'))]);
    }

    #[Route('/community/mentions', name: 'app_community_mentions', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function mentions(Request $request): JsonResponse
    {
        $q = trim($request->query->getString('q'));
        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        return $this->json($this->mentionService->autocomplete($q, $this->getAppUser()));
    }

    #[Route('/community/search/api', name: 'app_community_search_api', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchApi(Request $request): JsonResponse
    {
        $q = trim($request->query->getString('q'));
        if ($q === '') {
            return $this->json(['results' => [], 'counts' => []]);
        }

        $type = $request->query->getString('type', 'all') ?: 'all';
        $period = $request->query->getString('period') ?: null;

        $results = $this->searchService->search($q, $type, $period, $this->getAppUser());
        $counts = $this->searchService->countResults($q);

        return $this->json(['results' => $results, 'counts' => $counts]);
    }

    #[Route('/community/posts/{id}/translate', name: 'app_community_post_translate', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function translatePost(Request $request, CommunityPost $post): JsonResponse
    {
        $targetLang = $request->request->getString('lang', 'en');
        if (!in_array($targetLang, $this->translationService->getSupportedLanguages(), true)) {
            return $this->json(['error' => 'Unsupported language'], 400);
        }

        $content = $post->getContent();
        $sourceLang = $this->translationService->detectLanguage($content);
        $translated = $this->translationService->translate($content, $sourceLang, $targetLang);

        return $this->json([
            'translatedContent' => $translated,
            'detectedLanguage' => $sourceLang,
            'targetLanguage' => $targetLang,
        ]);
    }

    private function denyAdminSocialAction(): void
    {
        if ($this->getAppUser()->isAdmin()) {
            throw $this->createAccessDeniedException('Admins moderate community content instead of participating as users.');
        }
    }

    private function assertAdminToken(Request $request, CommunityPost $post, string $action): void
    {
        if (!$this->isCsrfTokenValid('admin_community_' . $action . '_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    /**
     * @param list<CommunityPost> $posts
     * @param array<string, mixed> $extra
     */
    private function renderFeed(array $posts, string $activeFeed, array $extra = []): Response
    {
        $user = $this->getAppUser();
        $bookmarkedIds = [];
        $myReactions = [];
        foreach ($posts as $post) {
            $postId = $post->getId();
            if ($postId === null) {
                continue;
            }
            if ($this->bookmarkRepository->findOneForUserAndPost($user, $post) !== null) {
                $bookmarkedIds[] = $postId;
            }
            $reaction = $this->reactionRepository->findOneForUserAndPost($user, $post);
            if ($reaction !== null) {
                $myReactions[$postId] = $reaction->getType()->value;
            }
        }

        $sharedPostIds = $this->hasTable('community_feed_shares')
            ? $this->shareRepository->findSharedPostIdsForUser($user, $posts)
            : [];

        return $this->render('community/feed/index.html.twig', array_merge([
            'posts' => $posts,
            'liked_post_ids' => $this->likedPostIds($user),
            'bookmarked_post_ids' => $bookmarkedIds,
            'shared_post_ids' => $sharedPostIds,
            'my_reactions' => $myReactions,
            'reaction_counts' => $this->reactionRepository->countByTypeForPosts($posts),
            'reaction_types' => CommunityReactionType::cases(),
            'active_feed' => $activeFeed,
        ], $extra));
    }

    /** @param array<string, int> $reactionCounts */
    private static function engagementScore(CommunityPost $post, array $reactionCounts): float
    {
        $reactions = array_sum($reactionCounts);
        $ageHours = max(1, (time() - $post->getCreatedAt()->getTimestamp()) / 3600);

        return ($reactions * 3) + ($post->getCommentsCount() * 2) + ($post->getSharesCount() * 1.5) + (24 / $ageHours);
    }

    private function createPostFromRequest(Request $request, User $user): CommunityPost
    {
        return (new CommunityPost())
            ->setAuthor($user)
            ->setContent($request->request->getString('content'))
            ->setVisibility(CommunityPostVisibility::tryFrom($request->request->getString('visibility')) ?? CommunityPostVisibility::PUBLIC)
            ->setMediaPath($this->storeMedia($request->files->get('media')));
    }

    private function moderateOrFlash(string $content): bool
    {
        $result = $this->moderationService->moderate($content);
        if ($result['safe']) {
            return true;
        }

        $this->addFlash('error', 'Please revise this content before posting. ' . ($result['reason'] ?? 'It may violate community guidelines.'));

        return false;
    }

    private function moderateImageOrFlash(string $mediaPath): bool
    {
        $fullPath = (string) $this->getParameter('kernel.project_dir') . '/public/' . $mediaPath;
        $result = $this->moderationService->moderateImage($fullPath);
        if ($result['safe']) {
            return true;
        }

        @unlink($fullPath);
        $this->addFlash('error', 'This image was flagged as inappropriate. ' . ($result['reason'] ?? 'Please upload a different image.'));

        return false;
    }

    private function storeMedia(mixed $file): ?string
    {
        if (!$file instanceof UploadedFile || $file->getError() !== UPLOAD_ERR_OK) {
            return null;
        }
        if (!str_starts_with((string) $file->getMimeType(), 'image/')) {
            $this->addFlash('error', 'Only image uploads are supported for community posts.');

            return null;
        }
        if ($file->getSize() !== null && $file->getSize() > 5 * 1024 * 1024) {
            $this->addFlash('error', 'Community images must be smaller than 5 MB.');

            return null;
        }

        $directory = (string) $this->getParameter('kernel.project_dir') . '/public/uploads/community';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $extension = $file->guessExtension() ?: 'bin';
        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        $file->move($directory, $filename);

        return 'uploads/community/' . $filename;
    }

    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json');
    }

    private function redirectBack(Request $request, CommunityPost $post): Response
    {
        $referer = $request->headers->get('Referer');
        if ($referer !== null && $referer !== '') {
            return $this->redirect($referer . '#post-' . $post->getId());
        }

        return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
    }

    private function legacyLike(Request $request, CommunityPost $post, User $user): Response
    {
        $like = $this->likeRepository->findOneForUserAndPost($user, $post);
        $active = $like === null;
        if ($like === null) {
            $this->entityManager->persist((new CommunityLike())->setUser($user)->setPost($post));
            $post->incrementLikes();
            $this->notifier->notifyPostAuthor($post, $user, 'community.post_liked', 'Someone liked your post', $user->getDisplayName() . ' liked your community post.');
        } else {
            $this->entityManager->remove($like);
            $post->decrementLikes();
        }
        $this->entityManager->flush();

        if ($this->wantsJson($request)) {
            return $this->json([
                'active' => $active,
                'counts' => ['like' => $post->getLikesCount()],
                'total' => $post->getLikesCount(),
                'fallback' => true,
            ]);
        }

        return $this->redirectBack($request, $post);
    }

    private function hasTable(string $tableName): bool
    {
        try {
            return $this->entityManager->getConnection()->createSchemaManager()->tablesExist([$tableName]);
        } catch (\Throwable) {
            return false;
        }
    }
}

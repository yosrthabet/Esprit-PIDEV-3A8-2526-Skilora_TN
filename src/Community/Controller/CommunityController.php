<?php

declare(strict_types=1);

namespace App\Community\Controller;

use App\Community\CommunityPostStatus;
use App\Community\Entity\CommunityComment;
use App\Community\Entity\CommunityLike;
use App\Community\Entity\CommunityPost;
use App\Community\Repository\CommunityLikeRepository;
use App\Community\Repository\CommunityPostRepository;
use App\Community\Service\CommunityNotifier;
use App\Controller\AppController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommunityController extends AppController
{
    public function __construct(
        private readonly CommunityPostRepository $postRepository,
        private readonly CommunityLikeRepository $likeRepository,
        private readonly CommunityNotifier $notifier,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/community', name: 'app_community', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user = $this->getAppUser();

        return $this->render('community/feed/index.html.twig', [
            'posts' => $this->postRepository->findPublished(),
            'liked_post_ids' => $this->likedPostIds($user),
        ]);
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
            $post = (new CommunityPost())
                ->setAuthor($user)
                ->setContent($request->request->getString('content'));
            if ($post->getContent() === '') {
                $this->addFlash('error', 'Post content is required.');

                return $this->redirectToRoute('app_community_post_new');
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
        }

        return $this->render('community/feed/new.html.twig');
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
            'liked' => $this->likeRepository->findOneForUserAndPost($this->getAppUser(), $post) !== null,
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
            $comment = (new CommunityComment())
                ->setPost($post)
                ->setAuthor($user)
                ->setContent($content);
            $post->incrementComments();
            $this->entityManager->persist($comment);
            $this->notifier->notifyPostAuthor($post, $user, 'community.comment_replied', 'New comment on your post', $content);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_community_post_show', ['id' => $post->getId()]);
    }

    #[Route('/community/posts/{id}/like', name: 'app_community_post_like', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function like(Request $request, CommunityPost $post): Response
    {
        $user = $this->getAppUser();
        $this->denyAdminSocialAction();
        $this->assertVisiblePost($post);
        if (!$this->isCsrfTokenValid('community_like_' . $post->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $like = $this->likeRepository->findOneForUserAndPost($user, $post);
        if ($like === null) {
            $this->entityManager->persist((new CommunityLike())->setUser($user)->setPost($post));
            $post->incrementLikes();
            $this->notifier->notifyPostAuthor($post, $user, 'community.post_liked', 'Someone liked your post', $user->getDisplayName() . ' liked your community post.');
        } else {
            $this->entityManager->remove($like);
            $post->decrementLikes();
        }
        $this->entityManager->flush();

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
}

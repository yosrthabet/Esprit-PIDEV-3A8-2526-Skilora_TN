<?php

declare(strict_types=1);

namespace App\Community\Controller;

use App\Community\BlogArticleStatus;
use App\Community\Entity\BlogArticle;
use App\Community\Entity\CommunityEvent;
use App\Community\Entity\CommunityGroup;
use App\Community\Entity\EventRsvp;
use App\Community\Repository\BlogArticleRepository;
use App\Community\Repository\CommunityEventRepository;
use App\Community\Repository\CommunityGroupRepository;
use App\Community\Repository\EventRsvpRepository;
use App\Community\Repository\GroupMemberRepository;
use App\Controller\AppController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommunitySpacesController extends AppController
{
    public function __construct(
        private readonly CommunityGroupRepository $groupRepository,
        private readonly GroupMemberRepository $memberRepository,
        private readonly CommunityEventRepository $eventRepository,
        private readonly EventRsvpRepository $rsvpRepository,
        private readonly BlogArticleRepository $articleRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/community/groups', name: 'app_community_groups', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function groups(): Response
    {
        return $this->render('community/groups/index.html.twig', ['groups' => $this->groupRepository->findRecent()]);
    }

    #[Route('/community/groups/new', name: 'app_community_group_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function groupNew(Request $request): Response
    {
        $this->denyAdminSocialAction();
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('community_group_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $group = (new CommunityGroup())
                ->setOwner($this->getAppUser())
                ->setName($request->request->getString('name'))
                ->setDescription($request->request->getString('description') ?: null);
            $group->addMember($this->getAppUser(), 'owner');
            $this->entityManager->persist($group);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        return $this->render('community/groups/new.html.twig');
    }

    #[Route('/community/groups/{id}', name: 'app_community_group_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function groupShow(CommunityGroup $group): Response
    {
        return $this->render('community/groups/show.html.twig', [
            'group' => $group,
            'membership' => $this->memberRepository->findOneForUserAndGroup($this->getAppUser(), $group),
        ]);
    }

    #[Route('/community/groups/{id}/join', name: 'app_community_group_join', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function groupJoin(Request $request, CommunityGroup $group): Response
    {
        $this->denyAdminSocialAction();
        if (!$this->isCsrfTokenValid('community_group_join_' . $group->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if ($this->memberRepository->findOneForUserAndGroup($this->getAppUser(), $group) === null) {
            $this->entityManager->persist($group->addMember($this->getAppUser()));
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
    }

    #[Route('/community/events', name: 'app_community_events', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function events(): Response
    {
        return $this->render('community/events/index.html.twig', ['events' => $this->eventRepository->findUpcoming()]);
    }

    #[Route('/community/events/new', name: 'app_community_event_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function eventNew(Request $request): Response
    {
        $this->denyAdminSocialAction();
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('community_event_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $event = (new CommunityEvent())
                ->setHost($this->getAppUser())
                ->setTitle($request->request->getString('title'))
                ->setDescription($request->request->getString('description') ?: null)
                ->setLocation($request->request->getString('location') ?: null)
                ->setOnlineUrl($request->request->getString('online_url') ?: null)
                ->setStartsAt(new \DateTimeImmutable($request->request->getString('starts_at') ?: '+1 week'));
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_community_event_show', ['id' => $event->getId()]);
        }

        return $this->render('community/events/new.html.twig');
    }

    #[Route('/community/events/{id}', name: 'app_community_event_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function eventShow(CommunityEvent $event): Response
    {
        return $this->render('community/events/show.html.twig', [
            'event' => $event,
            'rsvp' => $this->rsvpRepository->findOneForUserAndEvent($this->getAppUser(), $event),
        ]);
    }

    #[Route('/community/events/{id}/rsvp', name: 'app_community_event_rsvp', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function eventRsvp(Request $request, CommunityEvent $event): Response
    {
        $this->denyAdminSocialAction();
        if (!$this->isCsrfTokenValid('community_event_rsvp_' . $event->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if ($this->rsvpRepository->findOneForUserAndEvent($this->getAppUser(), $event) === null) {
            $this->entityManager->persist((new EventRsvp())->setEvent($event)->setUser($this->getAppUser()));
            $event->incrementRsvps();
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_community_event_show', ['id' => $event->getId()]);
    }

    #[Route('/community/blog', name: 'app_community_blog', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function blog(): Response
    {
        return $this->render('community/blog/index.html.twig', ['articles' => $this->articleRepository->findPublished()]);
    }

    #[Route('/community/blog/new', name: 'app_community_blog_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function blogNew(Request $request): Response
    {
        $this->denyAdminSocialAction();
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('community_blog_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $article = (new BlogArticle())
                ->setAuthor($this->getAppUser())
                ->setTitle($request->request->getString('title'))
                ->setSlug($this->slugify($request->request->getString('title')) . '-' . bin2hex(random_bytes(3)))
                ->setExcerpt($request->request->getString('excerpt') ?: null)
                ->setContent($request->request->getString('content'))
                ->setStatus(BlogArticleStatus::PUBLISHED);
            $this->entityManager->persist($article);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_community_blog_show', ['slug' => $article->getSlug()]);
        }

        return $this->render('community/blog/new.html.twig');
    }

    #[Route('/community/blog/{slug}', name: 'app_community_blog_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function blogShow(BlogArticle $article): Response
    {
        if (!$article->isPublished() && !$this->getAppUser()->isAdmin() && $article->getAuthor()->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('community/blog/show.html.twig', ['article' => $article]);
    }

    private function denyAdminSocialAction(): void
    {
        if ($this->getAppUser()->isAdmin()) {
            throw $this->createAccessDeniedException('Admins moderate community content instead of participating as users.');
        }
    }

    private function slugify(string $value): string
    {
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', trim($value)));
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'article';
    }
}

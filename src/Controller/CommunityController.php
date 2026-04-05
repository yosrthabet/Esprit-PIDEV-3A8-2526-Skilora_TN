<?php

namespace App\Controller;

use App\Entity\CommunityPost;
use App\Entity\User;
use App\Form\CommunityPostType;
use App\Repository\CommunityPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/community')]
#[IsGranted('ROLE_USER')]
class CommunityController extends AbstractController
{
    #[Route('', name: 'app_community', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('community/index.html.twig');
    }

    #[Route('/posts', name: 'app_community_posts', methods: ['GET', 'POST'])]
    public function posts(
        Request $request,
        CommunityPostRepository $posts,
        EntityManagerInterface $em,
    ): Response {
        $post = new CommunityPost();
        $post->setAuthor($this->getUser());

        $form = $this->createForm(CommunityPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($post);
            $em->flush();
            $this->addFlash('success', 'Publication publiée.');

            return $this->redirectToRoute('app_community_posts');
        }

        return $this->render('community/posts/index.html.twig', [
            'form' => $form,
            'posts' => $posts->findRecentFeed(),
        ]);
    }

    #[Route('/posts/{id}/edit', name: 'app_community_post_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        CommunityPost $post,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isAuthor($post)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CommunityPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Publication mise à jour.');

            return $this->redirectToRoute('app_community_posts');
        }

        return $this->render('community/posts/edit.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'app_community_post_delete', methods: ['POST'])]
    public function delete(Request $request, CommunityPost $post, EntityManagerInterface $em): Response
    {
        if (!$this->isAuthor($post)) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_post_'.$post->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_community_posts');
        }

        $em->remove($post);
        $em->flush();
        $this->addFlash('success', 'Publication supprimée.');

        return $this->redirectToRoute('app_community_posts');
    }

    private function isAuthor(CommunityPost $post): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $post->getAuthor()->getId() === $user->getId();
    }
}

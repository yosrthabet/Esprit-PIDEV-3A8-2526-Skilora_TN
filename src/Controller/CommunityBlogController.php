<?php

namespace App\Controller;

use App\Entity\BlogArticle;
use App\Entity\User;
use App\Form\BlogArticleType;
use App\Repository\BlogArticleRepository;
use App\Service\AISummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/community/blog')]
#[IsGranted('ROLE_USER')]
class CommunityBlogController extends AbstractController
{
    #[Route('', name: 'app_community_blog', methods: ['GET'])]
    public function index(BlogArticleRepository $blogRepo): Response
    {
        return $this->render('community/blog/index.html.twig', [
            'articles' => $blogRepo->findPublished(),
            'categories' => $blogRepo->findAllCategories(),
        ]);
    }

    #[Route('/new', name: 'app_community_blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $article = new BlogArticle();
        $article->setAuthor($this->requireUser());

        $form = $this->createForm(BlogArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($article);
            $em->flush();
            $this->addFlash('success', 'Article créé avec succès.');
            return $this->redirectToRoute('app_community_blog');
        }

        return $this->render('community/blog/form.html.twig', [
            'form' => $form,
            'article' => $article,
            'is_new' => true,
        ]);
    }

    #[Route('/{id}', name: 'app_community_blog_show', methods: ['GET'])]
    public function show(BlogArticle $article, EntityManagerInterface $em): Response
    {
        $article->incrementViews();
        $em->flush();

        return $this->render('community/blog/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_community_blog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BlogArticle $article, EntityManagerInterface $em): Response
    {
        if ($article->getAuthor()->getId() !== $this->requireUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(BlogArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Article mis à jour.');
            return $this->redirectToRoute('app_community_blog_show', ['id' => $article->getId()]);
        }

        return $this->render('community/blog/form.html.twig', [
            'form' => $form,
            'article' => $article,
            'is_new' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_community_blog_delete', methods: ['POST'])]
    public function delete(Request $request, BlogArticle $article, EntityManagerInterface $em): Response
    {
        if ($article->getAuthor()->getId() !== $this->requireUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_blog_' . $article->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_community_blog');
        }

        $em->remove($article);
        $em->flush();
        $this->addFlash('success', 'Article supprimé.');

        return $this->redirectToRoute('app_community_blog');
    }

    #[Route('/{id}/summarize', name: 'app_community_blog_summarize', methods: ['POST'])]
    public function summarize(BlogArticle $article, AISummaryService $aiService): Response
    {
        $summary = $aiService->summarize($article->getContent());

        return $this->json(['summary' => $summary]);
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

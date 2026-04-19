<?php

namespace App\Controller\Community;

use App\Entity\CommunityPost;
use App\Entity\User;
use App\Form\CommunityPostType;
use App\Repository\CommunityPostRepository;
use App\Service\ContentModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

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
        ContentModerationService $moderation,
        SluggerInterface $slugger,
    ): Response {
        $post = new CommunityPost();
        $post->setAuthor($this->getUser());

        $form = $this->createForm(CommunityPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── Upload de l'image ──
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            $uploadedFilePath = null;

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
                    $imageFile->move($uploadDir, $newFilename);
                    $uploadedFilePath = $uploadDir . '/' . $newFilename;
                    $post->setImageUrl('/uploads/posts/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');

                    return $this->render('community/posts/index.html.twig', [
                        'form' => $form,
                        'posts' => $posts->findRecentFeed(),
                    ]);
                }
            }

            // ── Modération AI du contenu avant publication ──
            $moderationResult = $moderation->moderatePost(
                $post->getContent(),
                null,
                $uploadedFilePath
            );

            if (!$moderationResult['safe']) {
                // Supprimer l'image uploadée si le post est bloqué
                if ($uploadedFilePath && file_exists($uploadedFilePath)) {
                    unlink($uploadedFilePath);
                }
                $post->setImageUrl(null);

                foreach ($moderationResult['reasons'] as $reason) {
                    $this->addFlash('error', '🚫 ' . $reason);
                    $this->addFlash('moderation', $reason);
                }

                return $this->render('community/posts/index.html.twig', [
                    'form' => $form,
                    'posts' => $posts->findRecentFeed(),
                ]);
            }

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
        ContentModerationService $moderation,
        SluggerInterface $slugger,
    ): Response {
        if (!$this->isAuthor($post)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CommunityPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── Upload de l'image ──
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            $uploadedFilePath = null;

            if ($imageFile) {
                // Supprimer l'ancienne image
                if ($post->getImageUrl()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $post->getImageUrl();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
                $imageFile->move($uploadDir, $newFilename);
                $uploadedFilePath = $uploadDir . '/' . $newFilename;
                $post->setImageUrl('/uploads/posts/' . $newFilename);
            }

            // ── Modération AI du contenu avant modification ──
            $existingImagePath = null;
            if (!$uploadedFilePath && $post->getImageUrl()) {
                $existingImagePath = $this->getParameter('kernel.project_dir') . '/public' . $post->getImageUrl();
            }

            $moderationResult = $moderation->moderatePost(
                $post->getContent(),
                null,
                $uploadedFilePath ?? $existingImagePath
            );

            if (!$moderationResult['safe']) {
                if ($uploadedFilePath && file_exists($uploadedFilePath)) {
                    unlink($uploadedFilePath);
                }

                foreach ($moderationResult['reasons'] as $reason) {
                    $this->addFlash('error', '🚫 ' . $reason);
                }

                return $this->render('community/posts/edit.html.twig', [
                    'form' => $form,
                    'post' => $post,
                ]);
            }

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

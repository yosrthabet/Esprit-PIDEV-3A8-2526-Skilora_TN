<?php

namespace App\Controller\Admin;

use App\Entity\CommunityPost;
use App\Repository\CommunityPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/community', name: 'admin_community_')]
#[IsGranted('ROLE_ADMIN')]
class CommunityController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        CommunityPostRepository $postRepo,
    ): Response {
        $search = $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $qb = $postRepo->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->orderBy('p.createdAt', 'DESC');

        if ($search !== '') {
            $q = '%' . mb_strtolower($search) . '%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(p.content) LIKE :q',
                'LOWER(COALESCE(a.username, \'\')) LIKE :q',
            ))->setParameter('q', $q);
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($total / $limit));

        $posts = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/community/_posts_table.html.twig', [
                'posts' => $posts,
                'currentPage' => $page,
                'totalPages' => $totalPages,
            ]);
        }

        return $this->render('admin/community/index.html.twig', [
            'posts' => $posts,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'searchQuery' => $search,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        CommunityPostRepository $postRepo,
        EntityManagerInterface $em,
    ): Response {
        $post = $postRepo->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        if (!$this->isCsrfTokenValid('post_delete_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_community_index');
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Post deleted successfully.');
        return $this->redirectToRoute('admin_community_index');
    }
}

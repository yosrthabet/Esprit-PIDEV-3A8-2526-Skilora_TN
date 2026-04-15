<?php

namespace App\Controller\Admin;

use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\ApplicationRepository;
use App\Recruitment\Repository\CompanyRepository;
use App\Recruitment\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/recruitment', name: 'admin_recruitment_')]
#[IsGranted('ROLE_ADMIN')]
class RecruitmentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        JobOfferRepository $jobOfferRepo,
        CompanyRepository $companyRepo,
        ApplicationRepository $applicationRepo,
    ): Response {
        $search = $request->query->get('q', '');
        $statusFilter = $request->query->get('status', 'all');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 15;

        $qb = $jobOfferRepo->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->addSelect('c')
            ->orderBy('j.postedDate', 'DESC')
            ->addOrderBy('j.id', 'DESC');

        if ($statusFilter !== 'all') {
            $qb->andWhere('j.status = :status')->setParameter('status', strtoupper($statusFilter));
        }

        if ($search !== '') {
            $q = '%' . mb_strtolower($search) . '%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(j.title) LIKE :q',
                'LOWER(COALESCE(j.location, \'\')) LIKE :q',
                'LOWER(COALESCE(j.companyName, \'\')) LIKE :q',
                'LOWER(COALESCE(c.name, \'\')) LIKE :q',
            ))->setParameter('q', $q);
        }

        // Count total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(j.id)')->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($total / $limit));

        // Paginate
        $offers = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Stats
        $statusCounts = $jobOfferRepo->createQueryBuilder('j')
            ->select('j.status, COUNT(j.id) as cnt')
            ->groupBy('j.status')
            ->getQuery()
            ->getResult();

        $stats = [
            'total' => $total,
            'companies' => $companyRepo->count([]),
            'status_counts' => array_column($statusCounts, 'cnt', 'status'),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/recruitment/_offers_table.html.twig', [
                'offers' => $offers,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'searchQuery' => $search,
            ]);
        }

        return $this->render('admin/recruitment/index.html.twig', [
            'offers' => $offers,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'searchQuery' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        JobOfferRepository $jobOfferRepo,
        ApplicationRepository $applicationRepo,
    ): Response {
        $offer = $jobOfferRepo->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')->addSelect('c')
            ->where('j.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$offer) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        $applications = $applicationRepo->findBy(
            ['jobOffer' => $offer],
            ['appliedDate' => 'DESC']
        );

        return $this->render('admin/recruitment/show.html.twig', [
            'offer' => $offer,
            'applications' => $applications,
        ]);
    }

    #[Route('/{id}/status', name: 'update_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        JobOfferRepository $jobOfferRepo,
        EntityManagerInterface $em,
    ): Response {
        $offer = $jobOfferRepo->find($id);
        if (!$offer) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        if (!$this->isCsrfTokenValid('offer_status_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_recruitment_show', ['id' => $id]);
        }

        $newStatus = $request->request->get('status');
        $allowed = ['OPEN', 'CLOSED', 'DRAFT', 'FROZEN'];
        if (!in_array($newStatus, $allowed, true)) {
            $this->addFlash('error', 'Invalid status.');
            return $this->redirectToRoute('admin_recruitment_show', ['id' => $id]);
        }

        $offer->setStatus($newStatus);
        $em->flush();

        $this->addFlash('success', 'Offer status updated to ' . $newStatus . '.');
        return $this->redirectToRoute('admin_recruitment_show', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        JobOfferRepository $jobOfferRepo,
        EntityManagerInterface $em,
    ): Response {
        $offer = $jobOfferRepo->find($id);
        if (!$offer) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        if (!$this->isCsrfTokenValid('offer_delete_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_recruitment_index');
        }

        $em->remove($offer);
        $em->flush();

        $this->addFlash('success', 'Job offer "' . $offer->getTitle() . '" has been deleted.');
        return $this->redirectToRoute('admin_recruitment_index');
    }

    #[Route('/{id}/toggle-featured', name: 'toggle_featured', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleFeatured(
        int $id,
        Request $request,
        JobOfferRepository $jobOfferRepo,
        EntityManagerInterface $em,
    ): Response {
        $offer = $jobOfferRepo->find($id);
        if (!$offer) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        if (!$this->isCsrfTokenValid('offer_feature_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_recruitment_show', ['id' => $id]);
        }

        $offer->setIsFeatured(!$offer->isFeatured());
        $em->flush();

        $label = $offer->isFeatured() ? 'featured' : 'unfeatured';
        $this->addFlash('success', 'Offer marked as ' . $label . '.');
        return $this->redirectToRoute('admin_recruitment_show', ['id' => $id]);
    }
}

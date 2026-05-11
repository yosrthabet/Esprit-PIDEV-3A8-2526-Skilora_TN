<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Enum\JobOfferStatus;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\ApplicationRepository;
use App\Recruitment\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RecruitmentController extends AppController
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/recruitment', name: 'app_admin_recruitment', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = trim($request->query->getString('q')) ?: null;
        $status = $request->query->getString('status', 'all');
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 25;

        $qb = $this->entityManager->createQueryBuilder()
            ->select('j')
            ->from(JobOffer::class, 'j')
            ->orderBy('j.postedAt', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult(($page - 1) * $perPage);

        if ($q !== null) {
            $qb->andWhere('j.title LIKE :q OR j.companyName LIKE :q OR j.location LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        if ($status !== 'all') {
            $st = JobOfferStatus::tryFrom($status);
            if ($st !== null) {
                $qb->andWhere('j.status = :status')->setParameter('status', $st);
            }
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(j.id)')->setMaxResults(null)->setFirstResult(0);
        $total = (int) $countQb->getQuery()->getSingleScalarResult();
        /** @var list<JobOffer> $jobs */
        $jobs = $qb->getQuery()->getResult();

        $statusCounts = [];
        foreach (JobOfferStatus::cases() as $case) {
            $statusCounts[$case->value] = (int) $this->entityManager->createQueryBuilder()
                ->select('COUNT(j.id)')
                ->from(JobOffer::class, 'j')
                ->where('j.status = :s')
                ->setParameter('s', $case)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->render('recruitment/admin/index.html.twig', [
            'jobs' => $jobs,
            'q' => $q,
            'status' => $status,
            'statuses' => JobOfferStatus::cases(),
            'status_counts' => $statusCounts,
            'total' => $total,
            'page' => $page,
            'page_count' => max(1, (int) ceil($total / $perPage)),
            'total_applications' => $this->applicationRepository->count([]),
            'total_companies' => $this->companyRepository->count([]),
        ]);
    }

    #[Route('/admin/recruitment/{id}', name: 'app_admin_recruitment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(JobOffer $jobOffer): Response
    {
        $applications = $this->applicationRepository->findBy(['jobOffer' => $jobOffer], ['appliedAt' => 'DESC']);

        return $this->render('recruitment/admin/show.html.twig', [
            'job' => $jobOffer,
            'applications' => $applications,
        ]);
    }

    #[Route('/admin/recruitment/{id}/status', name: 'app_admin_recruitment_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeStatus(Request $request, JobOffer $jobOffer): Response
    {
        if (!$this->isCsrfTokenValid('admin_job_status_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $newStatus = JobOfferStatus::tryFrom($request->request->getString('status'));
        if ($newStatus !== null) {
            if ($newStatus === JobOfferStatus::CLOSED) {
                $jobOffer->close();
            } elseif ($newStatus === JobOfferStatus::OPEN) {
                $jobOffer->reopen();
            } else {
                $jobOffer->setStatus($newStatus);
            }
            $this->entityManager->flush();
            $this->addFlash('success', 'Job offer status updated to ' . $newStatus->value . '.');
        }

        return $this->redirectToRoute('app_admin_recruitment_show', ['id' => $jobOffer->getId()]);
    }

    #[Route('/admin/recruitment/{id}/delete', name: 'app_admin_recruitment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, JobOffer $jobOffer): Response
    {
        if (!$this->isCsrfTokenValid('admin_job_delete_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->entityManager->remove($jobOffer);
        $this->entityManager->flush();
        $this->addFlash('success', 'Job offer deleted.');

        return $this->redirectToRoute('app_admin_recruitment');
    }

    #[Route('/admin/recruitment/{id}/toggle-featured', name: 'app_admin_recruitment_toggle_featured', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleFeatured(Request $request, JobOffer $jobOffer): Response
    {
        if (!$this->isCsrfTokenValid('admin_job_feature_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $jobOffer->setFeatured(!$jobOffer->isFeatured());
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_recruitment_show', ['id' => $jobOffer->getId()]);
    }
}

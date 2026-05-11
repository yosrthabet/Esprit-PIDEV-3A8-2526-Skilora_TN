<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Entity\Contract;
use App\Finance\Entity\JobReview;
use App\Finance\Repository\JobReviewRepository;
use App\Finance\Service\ReviewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReviewController extends AppController
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly JobReviewRepository $reviewRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/finance/contracts/{id}/review', name: 'app_finance_contract_review', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function submitReview(Contract $contract, Request $request): Response
    {
        $user = $this->getAppUser();
        if (!$this->isCsrfTokenValid('review_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $reviewedUser = $contract->getFreelancer()->getId() === $user->getId()
            ? $contract->getEmployer()
            : $contract->getFreelancer();

        if ($reviewedUser === null) {
            throw $this->createNotFoundException('Reviewed user not found.');
        }

        if ($this->reviewService->hasReviewed($contract, $user)) {
            $this->addFlash('error', 'You have already reviewed this contract.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        $this->reviewService->submitReview(
            $contract,
            $user,
            $reviewedUser,
            $request->request->getInt('rating', 3),
            $request->request->getString('comment') ?: null,
            $request->request->getInt('communication_rating') ?: null,
            $request->request->getInt('quality_rating') ?: null,
            $request->request->getInt('timeliness_rating') ?: null,
        );

        $this->addFlash('success', 'Review submitted.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/admin/finance/reviews', name: 'app_admin_finance_reviews', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        return $this->render('finance/admin/reviews.html.twig', [
            'reviews' => $this->reviewRepository->findBy([], ['createdAt' => 'DESC'], 50),
        ]);
    }

    #[Route('/admin/finance/reviews/{id}/delete', name: 'app_admin_finance_review_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDelete(JobReview $review, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_review_' . $review->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->entityManager->remove($review);
        $this->entityManager->flush();
        $this->addFlash('success', 'Review deleted.');

        return $this->redirectToRoute('app_admin_finance_reviews');
    }
}

<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Entity\Contract;
use App\Finance\Entity\ContractDispute;
use App\Finance\Entity\JobReview;
use App\Finance\Repository\ContractDisputeRepository;
use App\Finance\Repository\ContractMilestoneRepository;
use App\Finance\Repository\ContractRepository;
use App\Finance\Repository\JobReviewRepository;
use App\Finance\Service\ContractService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ContractController extends AppController
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly ContractDisputeRepository $disputeRepository,
        private readonly ContractMilestoneRepository $milestoneRepository,
        private readonly JobReviewRepository $jobReviewRepository,
        private readonly ContractService $contractService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/contracts', name: 'app_contracts', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('finance/contracts/index.html.twig', [
            'contracts' => $this->contractRepository->findForUser($this->getAppUser()),
        ]);
    }

    #[Route('/contracts/{id}', name: 'app_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Contract $contract): Response
    {
        $this->contractService->assertParticipant($this->getAppUser(), $contract);

        return $this->render('finance/contracts/show.html.twig', [
            'contract' => $contract,
            'milestones' => $this->milestoneRepository->findForContract($contract),
        ]);
    }

    #[Route('/contracts/{id}/fund', name: 'app_contract_fund', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function fund(Request $request, Contract $contract): Response
    {
        if (!$this->isCsrfTokenValid('fund_contract_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->contractService->fundEscrow($this->getAppUser(), $contract);
        $this->addFlash('success', 'Escrow marked as funded for this contract.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/contracts/{id}/deliveries', name: 'app_contract_delivery_submit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function submitDelivery(Request $request, Contract $contract): Response
    {
        if (!$this->isCsrfTokenValid('submit_delivery_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->contractService->submitDelivery(
            $this->getAppUser(),
            $contract,
            $request->request->getString('title'),
            $request->request->getString('message'),
            $request->request->getString('attachment_url') ?: null,
        );
        $this->addFlash('success', 'Delivery submitted for employer review.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/contracts/{id}/approve', name: 'app_contract_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(Request $request, Contract $contract): Response
    {
        if (!$this->isCsrfTokenValid('approve_contract_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->contractService->approveDelivery($this->getAppUser(), $contract);
        $this->addFlash('success', 'Delivery approved. You can now release escrow.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/contracts/{id}/release', name: 'app_contract_release', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function release(Request $request, Contract $contract): Response
    {
        if (!$this->isCsrfTokenValid('release_contract_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->contractService->releaseEscrow($this->getAppUser(), $contract);
        $this->addFlash('success', 'Escrow released and invoice marked paid.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/contracts/{id}/dispute', name: 'app_contract_dispute', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function dispute(Request $request, Contract $contract): Response
    {
        if (!$this->isCsrfTokenValid('dispute_contract_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->contractService->openDispute($this->getAppUser(), $contract, $request->request->getString('reason'), $request->request->getString('details') ?: null);
        $this->addFlash('success', 'Dispute opened. Admins have been notified.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/contracts/{id}/review', name: 'app_contract_review', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function review(Request $request, Contract $contract): Response
    {
        $user = $this->getAppUser();
        $this->contractService->assertParticipant($user, $contract);
        if (!$this->isCsrfTokenValid('review_contract_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if ($this->jobReviewRepository->findOneForContractAndReviewer($contract, $user) !== null) {
            $this->addFlash('info', 'You have already reviewed this contract.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }
        $employer = $contract->getEmployer();
        $freelancer = $contract->getFreelancer();
        $other = ($employer !== null && $employer->getId() === $user->getId()) ? $freelancer : $employer;
        if ($other === null) {
            throw $this->createNotFoundException('Cannot determine the other party.');
        }
        $review = (new JobReview())
            ->setContract($contract)
            ->setReviewer($user)
            ->setReviewedUser($other)
            ->setRating($request->request->getInt('rating', 5))
            ->setComment(trim($request->request->getString('comment')) ?: null)
            ->setCommunicationRating($request->request->getInt('communication') ?: null)
            ->setQualityRating($request->request->getInt('quality') ?: null)
            ->setTimelinessRating($request->request->getInt('timeliness') ?: null);
        $this->entityManager->persist($review);
        $this->entityManager->flush();
        $this->addFlash('success', 'Review submitted.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/admin/finance', name: 'app_admin_finance', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        $allContracts = $this->contractRepository->findAll();
        $totalVolume = 0.0;
        $activeCount = 0;
        foreach ($allContracts as $c) {
            $totalVolume += (float) $c->getAmount();
            if ($c->getEscrowFundedAt() !== null && $c->getReleasedAt() === null) {
                ++$activeCount;
            }
        }

        return $this->render('finance/admin/index.html.twig', [
            'contracts' => $this->contractRepository->findRecent(),
            'disputes' => $this->disputeRepository->findOpen(),
            'total_contracts' => count($allContracts),
            'active_escrow_count' => $activeCount,
            'total_volume' => number_format($totalVolume, 2, '.', ' '),
            'open_dispute_count' => count($this->disputeRepository->findOpen()),
        ]);
    }

    #[Route('/admin/finance/contracts/{id}', name: 'app_admin_finance_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminContractShow(Contract $contract): Response
    {
        return $this->render('finance/contracts/show.html.twig', ['contract' => $contract]);
    }

    #[Route('/admin/finance/disputes/{id}/resolve', name: 'app_admin_finance_dispute_resolve', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resolveDispute(Request $request, ContractDispute $dispute): Response
    {
        if (!$this->isCsrfTokenValid('resolve_dispute_' . $dispute->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->contractService->resolveDispute($dispute, $request->request->getString('resolution'));
        $this->addFlash('success', 'Dispute resolved and contract closed.');

        return $this->redirectToRoute('app_admin_finance');
    }
}

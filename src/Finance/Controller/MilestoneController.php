<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Enum\Currency;
use App\Finance\Entity\Contract;
use App\Finance\Entity\ContractMilestone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class MilestoneController extends AppController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/contracts/{id}/milestones', name: 'app_contract_milestone_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function create(Request $request, Contract $contract): Response
    {
        $employer = $contract->getEmployer();
        if ($employer?->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException('Only the employer can create milestones.');
        }
        if (!$this->isCsrfTokenValid('milestone_new_' . $contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $milestone = (new ContractMilestone())
            ->setContract($contract)
            ->setTitle($request->request->getString('title'))
            ->setDescription($request->request->getString('description') ?: null)
            ->setAmount(number_format(max(0, (float) $request->request->getString('amount')), 2, '.', ''))
            ->setCurrency(Currency::tryFrom($request->request->getString('currency')) ?? $contract->getCurrency())
            ->setDueAt($request->request->getString('due_at') !== '' ? new \DateTimeImmutable($request->request->getString('due_at')) : null);
        $this->entityManager->persist($milestone);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/contracts/{contract}/milestones/{milestone}/pay', name: 'app_contract_milestone_pay', methods: ['POST'], requirements: ['contract' => '\d+', 'milestone' => '\d+'])]
    public function pay(Request $request, Contract $contract, ContractMilestone $milestone): Response
    {
        $this->assertMilestoneAction($request, $contract, $milestone, 'pay');
        $milestone->markPaid();
        $this->entityManager->flush();

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/contracts/{contract}/milestones/{milestone}/cancel', name: 'app_contract_milestone_cancel', methods: ['POST'], requirements: ['contract' => '\d+', 'milestone' => '\d+'])]
    public function cancel(Request $request, Contract $contract, ContractMilestone $milestone): Response
    {
        $this->assertMilestoneAction($request, $contract, $milestone, 'cancel');
        $milestone->cancel();
        $this->entityManager->flush();

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    private function assertMilestoneAction(Request $request, Contract $contract, ContractMilestone $milestone, string $action): void
    {
        if ($milestone->getContract()->getId() !== $contract->getId() || $contract->getEmployer()?->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('milestone_' . $action . '_' . $milestone->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}

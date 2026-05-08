<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Enum\ContractStatus;
use App\Enum\DisputeStatus;
use App\Finance\Entity\Contract;
use App\Finance\Entity\ContractDispute;
use App\Finance\Repository\ContractRepository;
use App\Finance\Repository\EscrowTransactionRepository;
use App\Finance\Repository\InvoiceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class FinanceDashboardController extends AppController
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly EscrowTransactionRepository $transactionRepository,
        private readonly InvoiceRepository $invoiceRepository,
    ) {
    }

    #[Route('/finance', name: 'app_finance', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getAppUser();
        $contracts = $this->contractRepository->findForUser($user);
        $openDisputes = $this->openDisputes($contracts);

        return $this->render('finance/dashboard/index.html.twig', [
            'contracts' => array_slice($contracts, 0, 6),
            'contract_count' => count($contracts),
            'status_counts' => $this->statusCounts($contracts),
            'active_escrow_total' => $this->activeEscrowTotal($contracts),
            'paid_invoice_total' => $this->invoiceRepository->sumPaidForUser($user),
            'open_disputes' => array_slice($openDisputes, 0, 5),
            'open_dispute_count' => count($openDisputes),
            'recent_transactions' => $this->transactionRepository->findForUser($user, 8),
        ]);
    }

    /**
     * @param list<Contract> $contracts
     * @return array<string, int>
     */
    private function statusCounts(array $contracts): array
    {
        $counts = [];
        foreach (ContractStatus::cases() as $status) {
            $counts[$status->value] = 0;
        }

        foreach ($contracts as $contract) {
            ++$counts[$contract->getStatus()->value];
        }

        return $counts;
    }

    /** @param list<Contract> $contracts */
    private function activeEscrowTotal(array $contracts): float
    {
        $total = 0.0;
        foreach ($contracts as $contract) {
            if ($contract->getEscrowFundedAt() !== null && $contract->getReleasedAt() === null) {
                $total += (float) $contract->getAmount();
            }
        }

        return $total;
    }

    /**
     * @param list<Contract> $contracts
     * @return list<ContractDispute>
     */
    private function openDisputes(array $contracts): array
    {
        $disputes = [];
        foreach ($contracts as $contract) {
            foreach ($contract->getDisputes() as $dispute) {
                if ($dispute->getStatus() === DisputeStatus::OPEN) {
                    $disputes[] = $dispute;
                }
            }
        }

        usort($disputes, static fn (ContractDispute $a, ContractDispute $b): int => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $disputes;
    }
}

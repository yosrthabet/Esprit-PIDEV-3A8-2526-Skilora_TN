<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Repository\EscrowTransactionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class EscrowController extends AppController
{
    public function __construct(private readonly EscrowTransactionRepository $transactionRepository)
    {
    }

    #[Route('/finance/escrow', name: 'app_finance_escrow', methods: ['GET'])]
    #[Route('/workspace/escrow', name: 'app_escrow_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('finance/escrow/index.html.twig', [
            'transactions' => $this->transactionRepository->findForUser($this->getAppUser()),
        ]);
    }
}

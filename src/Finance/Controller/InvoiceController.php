<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Entity\Invoice;
use App\Finance\Repository\InvoiceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class InvoiceController extends AppController
{
    public function __construct(private readonly InvoiceRepository $invoiceRepository)
    {
    }

    #[Route('/finance/invoices', name: 'app_finance_invoices', methods: ['GET'])]
    #[Route('/mon-espace/factures', name: 'app_freelancer_invoices', methods: ['GET'])]
    #[Route('/employer/factures', name: 'app_employer_invoices', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('finance/invoices/index.html.twig', [
            'invoices' => $this->invoiceRepository->findForUser($this->getAppUser()),
        ]);
    }

    #[Route('/finance/invoices/{id}', name: 'app_finance_invoice_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Route('/facture/{id}', name: 'app_invoice_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Invoice $invoice): Response
    {
        if (!$this->invoiceRepository->isVisibleToUser($invoice, $this->getAppUser())) {
            throw $this->createAccessDeniedException('You cannot access this invoice.');
        }

        return $this->render('finance/invoices/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }
}

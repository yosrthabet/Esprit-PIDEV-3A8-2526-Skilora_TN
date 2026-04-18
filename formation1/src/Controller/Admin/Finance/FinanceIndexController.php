<?php

namespace App\Controller\Admin\Finance;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class FinanceIndexController extends AbstractController
{
    #[Route('/admin/finance', name: 'admin_finance_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->redirectToRoute('admin_finance_contract_index');
    }
}

<?php

namespace App\Controller\Admin\Finance;

use App\Entity\Finance\Contract;
use App\Form\Finance\ContractType;
use App\Repository\Finance\ContractRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/finance/contracts')]
#[IsGranted('ROLE_ADMIN')]
final class ContractController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContractRepository $contractRepository,
    ) {
    }

    #[Route('', name: 'admin_finance_contract_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = (string) $request->query->get('q', '');
        $contracts = $q !== ''
            ? $this->contractRepository->searchByEmployeeName($q)
            : $this->contractRepository->findAllOrdered();

        return $this->render('admin/finance/contract/index.html.twig', [
            'page_title' => 'Contrats',
            'contracts' => $contracts,
            'search_query' => $q,
        ]);
    }

    #[Route('/new', name: 'admin_finance_contract_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $contract = new Contract();
        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($contract);
            $this->entityManager->flush();
            $this->addFlash('success', 'Contract created.');

            return $this->redirectToRoute('admin_finance_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('admin/finance/contract/form.html.twig', [
            'page_title' => 'Nouveau contrat',
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'admin_finance_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Contract $contract): Response
    {
        return $this->render('admin/finance/contract/show.html.twig', [
            'page_title' => 'Contrat #'.$contract->getId(),
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_finance_contract_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Contract $contract): Response
    {
        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Contract updated.');

            return $this->redirectToRoute('admin_finance_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('admin/finance/contract/form.html.twig', [
            'page_title' => 'Modifier le contrat #'.$contract->getId(),
            'form' => $form,
            'is_edit' => true,
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_finance_contract_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Contract $contract): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$contract->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('admin_finance_contract_index');
        }

        try {
            $this->entityManager->remove($contract);
            $this->entityManager->flush();
            $this->addFlash('success', 'Contract deleted.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Cannot delete this contract: it is referenced by other records.');
        }

        return $this->redirectToRoute('admin_finance_contract_index');
    }
}

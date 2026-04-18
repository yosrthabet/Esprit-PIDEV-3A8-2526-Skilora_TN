<?php

namespace App\Controller\Admin\Finance;

use App\Entity\Finance\BankAccount;
use App\Form\Finance\BankAccountType;
use App\Repository\Finance\BankAccountRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/finance/bank-accounts')]
#[IsGranted('ROLE_ADMIN')]
final class BankAccountController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BankAccountRepository $bankAccountRepository,
    ) {
    }

    #[Route('', name: 'admin_finance_bank_account_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = (string) $request->query->get('q', '');
        $accounts = $q !== ''
            ? $this->bankAccountRepository->searchByEmployeeName($q)
            : $this->bankAccountRepository->findAllOrdered();

        return $this->render('admin/finance/bank_account/index.html.twig', [
            'page_title' => 'Comptes bancaires',
            'accounts' => $accounts,
            'search_query' => $q,
        ]);
    }

    #[Route('/new', name: 'admin_finance_bank_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $account = new BankAccount();
        $form = $this->createForm(BankAccountType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($account);
            $this->entityManager->flush();
            $this->addFlash('success', 'Bank account created.');

            return $this->redirectToRoute('admin_finance_bank_account_show', ['id' => $account->getId()]);
        }

        return $this->render('admin/finance/bank_account/form.html.twig', [
            'page_title' => 'Nouveau compte bancaire',
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'admin_finance_bank_account_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(BankAccount $bankAccount): Response
    {
        return $this->render('admin/finance/bank_account/show.html.twig', [
            'page_title' => 'Compte bancaire #'.$bankAccount->getId(),
            'account' => $bankAccount,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_finance_bank_account_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, BankAccount $bankAccount): Response
    {
        $form = $this->createForm(BankAccountType::class, $bankAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Bank account updated.');

            return $this->redirectToRoute('admin_finance_bank_account_show', ['id' => $bankAccount->getId()]);
        }

        return $this->render('admin/finance/bank_account/form.html.twig', [
            'page_title' => 'Modifier le compte #'.$bankAccount->getId(),
            'form' => $form,
            'is_edit' => true,
            'account' => $bankAccount,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_finance_bank_account_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, BankAccount $bankAccount): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$bankAccount->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('admin_finance_bank_account_index');
        }

        try {
            $this->entityManager->remove($bankAccount);
            $this->entityManager->flush();
            $this->addFlash('success', 'Bank account deleted.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Cannot delete this account: it is referenced by other records.');
        }

        return $this->redirectToRoute('admin_finance_bank_account_index');
    }
}

<?php

declare(strict_types=1);

namespace App\Finance\Controller;

use App\Controller\AppController;
use App\Finance\Entity\BankAccount;
use App\Finance\Repository\BankAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class BankAccountController extends AppController
{
    public function __construct(private readonly BankAccountRepository $repository, private readonly EntityManagerInterface $entityManager) {}

    #[Route('/finance/bank-accounts', name: 'app_finance_bank_accounts', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('bank_account_new_' . $this->getAppUser()->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $account = (new BankAccount())
                ->setUser($this->getAppUser())
                ->setBankName($request->request->getString('bank_name'))
                ->setAccountHolder($request->request->getString('account_holder'))
                ->setIban($request->request->getString('iban'))
                ->setSwift($request->request->getString('swift') ?: null)
                ->setPrimary($request->request->getBoolean('primary'));
            $this->entityManager->persist($account);
            $this->entityManager->flush();
            $this->addFlash('success', 'Bank account added.');

            return $this->redirectToRoute('app_finance_bank_accounts');
        }

        return $this->render('finance/bank_accounts/index.html.twig', ['accounts' => $this->repository->findForUser($this->getAppUser())]);
    }

    #[Route('/finance/bank-accounts/{id}/delete', name: 'app_finance_bank_account_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, BankAccount $account): Response
    {
        if ($account->getUser()->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('bank_account_delete_' . $account->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->entityManager->remove($account);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_finance_bank_accounts');
    }
}

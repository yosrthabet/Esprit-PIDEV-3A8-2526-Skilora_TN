<?php

namespace App\Controller\Admin\Finance;

use App\Entity\Finance\Bonus;
use App\Form\Finance\BonusType;
use App\Repository\Finance\BonusRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/finance/bonuses')]
#[IsGranted('ROLE_ADMIN')]
final class BonusController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BonusRepository $bonusRepository,
    ) {
    }

    #[Route('', name: 'admin_finance_bonus_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = (string) $request->query->get('q', '');
        $bonuses = $q !== ''
            ? $this->bonusRepository->searchByEmployeeName($q)
            : $this->bonusRepository->findAllOrdered();

        return $this->render('admin/finance/bonus/index.html.twig', [
            'page_title' => 'Primes',
            'bonuses' => $bonuses,
            'search_query' => $q,
        ]);
    }

    #[Route('/new', name: 'admin_finance_bonus_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $bonus = new Bonus();
        $form = $this->createForm(BonusType::class, $bonus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($bonus);
            $this->entityManager->flush();
            $this->addFlash('success', 'Bonus created.');

            return $this->redirectToRoute('admin_finance_bonus_show', ['id' => $bonus->getId()]);
        }

        return $this->render('admin/finance/bonus/form.html.twig', [
            'page_title' => 'Nouvelle prime',
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'admin_finance_bonus_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Bonus $bonus): Response
    {
        return $this->render('admin/finance/bonus/show.html.twig', [
            'page_title' => 'Prime #'.$bonus->getId(),
            'bonus' => $bonus,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_finance_bonus_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Bonus $bonus): Response
    {
        $form = $this->createForm(BonusType::class, $bonus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Bonus updated.');

            return $this->redirectToRoute('admin_finance_bonus_show', ['id' => $bonus->getId()]);
        }

        return $this->render('admin/finance/bonus/form.html.twig', [
            'page_title' => 'Modifier la prime #'.$bonus->getId(),
            'form' => $form,
            'is_edit' => true,
            'bonus' => $bonus,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_finance_bonus_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Bonus $bonus): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$bonus->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('admin_finance_bonus_index');
        }

        try {
            $this->entityManager->remove($bonus);
            $this->entityManager->flush();
            $this->addFlash('success', 'Bonus deleted.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Cannot delete this bonus: it is referenced by other records.');
        }

        return $this->redirectToRoute('admin_finance_bonus_index');
    }
}

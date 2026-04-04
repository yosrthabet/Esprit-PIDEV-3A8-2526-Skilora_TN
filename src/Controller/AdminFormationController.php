<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/formations')]
#[IsGranted('ROLE_ADMIN')]
final class AdminFormationController extends AbstractController
{
    private const PER_PAGE = 10;

    private const SEARCH_MAX_LEN = 200;

    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(name: 'app_admin_formation_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $qRaw = $request->query->getString('q');
        $qDisplay = $this->truncateQueryDisplay($qRaw);
        $search = '' !== trim($qRaw) ? trim($qRaw) : null;

        $items = $this->formationRepository->findPaginated($page, self::PER_PAGE, $search);
        $total = $this->formationRepository->countFiltered($search);
        $pageCount = max(1, (int) ceil($total / self::PER_PAGE));

        if ($page > $pageCount && $total > 0) {
            return $this->redirectToRoute('app_admin_formation_index', array_filter([
                'page' => $pageCount,
                'q' => $qDisplay,
            ], static fn ($v) => null !== $v && '' !== $v));
        }

        $from = $total > 0 ? ($page - 1) * self::PER_PAGE + 1 : 0;
        $to = $total > 0 ? min($page * self::PER_PAGE, $total) : 0;

        return $this->render('admin/formation/index.html.twig', [
            'formations' => $items,
            'currentPage' => $page,
            'pageCount' => $pageCount,
            'total' => $total,
            'perPage' => self::PER_PAGE,
            'rangeFrom' => $from,
            'rangeTo' => $to,
            'q' => $qDisplay,
        ]);
    }

    #[Route('/new', name: 'app_admin_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($formation);
            $this->entityManager->flush();
            $this->addFlash('success', $this->translator->trans('formation.flash.created', [
                '%title%' => $formation->getTitle(),
            ], 'messages'));

            return $this->redirectToRoute('app_admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/formation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_formation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Formation $formation): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', $this->translator->trans('formation.flash.updated', [
                '%title%' => $formation->getTitle(),
            ], 'messages'));

            return $this->redirectToRoute('app_admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_formation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Formation $formation): Response
    {
        $tokenId = 'formation_delete_'.$formation->getId();
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('formation.flash.csrf_invalid', [], 'messages'));

            return $this->redirectToRoute('app_admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        $title = $formation->getTitle();
        $this->entityManager->remove($formation);
        $this->entityManager->flush();
        $this->addFlash('success', $this->translator->trans('formation.flash.deleted', [
            '%title%' => $title,
        ], 'messages'));

        return $this->redirectToRoute('app_admin_formation_index', [], Response::HTTP_SEE_OTHER);
    }

    private function truncateQueryDisplay(string $q): string
    {
        if ('' === $q) {
            return '';
        }

        if (\function_exists('mb_substr')) {
            return mb_substr($q, 0, self::SEARCH_MAX_LEN);
        }

        return substr($q, 0, self::SEARCH_MAX_LEN);
    }
}

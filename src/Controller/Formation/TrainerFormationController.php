<?php

declare(strict_types=1);

namespace App\Controller\Formation;

use App\Entity\Formation;
use App\Entity\User;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer/formations')]
#[IsGranted('ROLE_TRAINER')]
final class TrainerFormationController extends AbstractController
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_trainer_formations', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $page = max(1, $request->query->getInt('page', 1));
        $q = trim($request->query->getString('q'));
        $search = $q !== '' ? $q : null;

        $formations = $this->formationRepository->findByTrainerPaginated($user->getId(), $page, self::PER_PAGE, $search);
        $total = $this->formationRepository->countByTrainer($user->getId(), $search);
        $pageCount = max(1, (int) ceil($total / self::PER_PAGE));

        return $this->render('trainer/formations/index.html.twig', [
            'formations' => $formations,
            'currentPage' => $page,
            'pageCount' => $pageCount,
            'total' => $total,
            'q' => $q,
        ]);
    }

    #[Route('/new', name: 'app_trainer_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isAjax = $request->headers->has('X-Requested-With');

        $formation = new Formation();
        $formation->setCreatedBy($user->getId());

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($formation);
            $this->entityManager->flush();

            if ($isAjax) {
                return new JsonResponse(['success' => true, 'message' => 'Formation created successfully.']);
            }

            $this->addFlash('success', 'Formation "' . $formation->getTitle() . '" created.');
            return $this->redirectToRoute('app_trainer_formations');
        }

        if ($isAjax && !$form->isSubmitted()) {
            return $this->render('trainer/formations/_dialog_form.html.twig', [
                'form' => $form,
                'formation' => null,
            ]);
        }

        if ($isAjax && $form->isSubmitted()) {
            return new JsonResponse(['success' => false, 'message' => 'Please fix the errors.'], 422);
        }

        return $this->render('trainer/formations/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_trainer_formation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Formation $formation): Response
    {
        $this->ensureOwner($formation);
        $isAjax = $request->headers->has('X-Requested-With');

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            if ($isAjax) {
                return new JsonResponse(['success' => true, 'message' => 'Formation updated.']);
            }

            $this->addFlash('success', 'Formation "' . $formation->getTitle() . '" updated.');
            return $this->redirectToRoute('app_trainer_formations');
        }

        if ($isAjax && !$form->isSubmitted()) {
            return $this->render('trainer/formations/_dialog_form.html.twig', [
                'form' => $form,
                'formation' => $formation,
            ]);
        }

        if ($isAjax && $form->isSubmitted()) {
            return new JsonResponse(['success' => false, 'message' => 'Please fix the errors.'], 422);
        }

        return $this->render('trainer/formations/edit.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_trainer_formation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Formation $formation): Response
    {
        $this->ensureOwner($formation);

        $tokenId = 'formation_delete_' . $formation->getId();
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            if ($request->headers->has('X-Requested-With')) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
            }
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_trainer_formations');
        }

        $title = $formation->getTitle();
        $this->entityManager->remove($formation);
        $this->entityManager->flush();

        if ($request->headers->has('X-Requested-With')) {
            return new JsonResponse(['success' => true, 'message' => 'Formation "' . $title . '" deleted.']);
        }

        $this->addFlash('success', 'Formation "' . $title . '" deleted.');
        return $this->redirectToRoute('app_trainer_formations');
    }

    private function ensureOwner(Formation $formation): void
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($formation->getCreatedBy() !== $user->getId()) {
            throw $this->createAccessDeniedException('You do not own this formation.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Formation;

use App\Certificate\Branding\FormationSignatureStorageInterface;
use App\Entity\Formation;
use App\Form\FormationType;
use App\Formation\FormationCertificateSignatureFormHandler;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        private readonly FormationSignatureStorageInterface $formationSignatureStorage,
        private readonly FormationCertificateSignatureFormHandler $formationCertificateSignatureFormHandler,
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
        $isAjax = $request->headers->has('X-Requested-With');
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation, [
            'show_signature_remove_checkbox' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->formationCertificateSignatureFormHandler->validateSubmittedSignature($form, $formation);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($formation);
            $this->entityManager->flush();
            $this->formationCertificateSignatureFormHandler->syncFromForm($form, $formation);

            if ($isAjax) {
                return new JsonResponse(['success' => true, 'message' => 'Formation créée avec succès.']);
            }

            $this->addFlash('success', $this->translator->trans('formation.flash.created', [
                '%title%' => $formation->getTitle(),
            ], 'messages'));

            return $this->redirectToRoute('app_admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($isAjax && !$form->isSubmitted()) {
            return $this->render('admin/formation/_dialog_form.html.twig', [
                'form' => $form,
                'formation' => null,
            ]);
        }

        if ($isAjax && $form->isSubmitted()) {
            $response = $this->render('admin/formation/_dialog_form.html.twig', [
                'form' => $form,
                'formation' => null,
            ]);
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);

            return $response;
        }

        return $this->render('admin/formation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/signature-preview', name: 'app_admin_formation_signature_preview', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function signaturePreview(Formation $formation): Response
    {
        $path = $this->formationSignatureStorage->getAbsolutePath($formation);
        if (null === $path) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Content-Disposition', 'inline; filename="signature.png"');

        return $response;
    }

    #[Route('/{id}/edit', name: 'app_admin_formation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Formation $formation): Response
    {
        $isAjax = $request->headers->has('X-Requested-With');
        $form = $this->createForm(FormationType::class, $formation, [
            'show_signature_remove_checkbox' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->formationCertificateSignatureFormHandler->validateSubmittedSignature($form, $formation);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->formationCertificateSignatureFormHandler->syncFromForm($form, $formation);

            if ($isAjax) {
                return new JsonResponse(['success' => true, 'message' => 'Formation mise à jour avec succès.']);
            }

            $this->addFlash('success', $this->translator->trans('formation.flash.updated', [
                '%title%' => $formation->getTitle(),
            ], 'messages'));

            return $this->redirectToRoute('app_admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($isAjax && !$form->isSubmitted()) {
            return $this->render('admin/formation/_dialog_form.html.twig', [
                'form' => $form,
                'formation' => $formation,
            ]);
        }

        if ($isAjax && $form->isSubmitted()) {
            $response = $this->render('admin/formation/_dialog_form.html.twig', [
                'form' => $form,
                'formation' => $formation,
            ]);
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);

            return $response;
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
            if ($request->headers->has('X-Requested-With')) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
            }
            $this->addFlash('error', $this->translator->trans('formation.flash.csrf_invalid', [], 'messages'));

            return $this->redirectToRoute('app_admin_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        $title = $formation->getTitle();
        $fid = (int) $formation->getId();
        $this->formationSignatureStorage->deleteAllFilesForFormationId($fid);
        $this->entityManager->remove($formation);
        $this->entityManager->flush();

        if ($request->headers->has('X-Requested-With')) {
            return new JsonResponse(['success' => true, 'message' => 'Formation "'.$title.'" supprimée.']);
        }

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

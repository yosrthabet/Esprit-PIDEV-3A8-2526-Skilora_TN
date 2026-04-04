<?php

declare(strict_types=1);

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Service\EmployerApplicationService;
use App\Recruitment\Service\EmployerContext;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employer', name: 'app_employer_')]
#[IsGranted('ROLE_EMPLOYER')]
final class EmployerApplicationsController extends AbstractController
{
    public function __construct(
        private readonly string $cvUploadDir,
        private readonly string $projectDir,
    ) {
    }

    #[Route('/candidatures', name: 'applications', methods: ['GET'])]
    public function index(
        EmployerContext $employerContext,
        ApplicationsTableGateway $applicationsTableGateway,
        UserRepository $userRepository,
    ): Response {
        $principal = $this->getUser();
        if (!$principal instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $user = $userRepository->find($principal->getId()) ?? $principal;
        $company = $employerContext->getCompanyForEmployer($user);

        $uid = $user->getId();
        if ($uid === null) {
            throw $this->createAccessDeniedException();
        }

        $candidatures = $applicationsTableGateway->fetchEmployerCandidatureListForDisplay((int) $uid);

        $total = \count($candidatures);
        $distinctCandidates = 0;
        $seen = [];
        foreach ($candidatures as $c) {
            $cid = $c->candidateUserId;
            if (!isset($seen[$cid])) {
                $seen[$cid] = true;
                ++$distinctCandidates;
            }
        }

        return $this->render('recrutement/employer/applications/index.html.twig', [
            'company' => $company,
            'candidatures' => $candidatures,
            'applications_total' => $total,
            'distinct_candidates' => $distinctCandidates,
        ]);
    }

    #[Route('/candidatures/{id}/profil', name: 'applications_profile', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function profile(
        int $id,
        ApplicationsTableGateway $applicationsTableGateway,
        UserRepository $userRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $uid = $user->getId();
        if ($uid === null) {
            throw $this->createAccessDeniedException();
        }

        $candidature = $applicationsTableGateway->fetchEmployerCandidatureProfileForEmployer($id, (int) $uid);
        if ($candidature === null) {
            throw $this->createNotFoundException('Candidature introuvable.');
        }

        return $this->render('recrutement/employer/applications/profile.html.twig', [
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidatures/{id}/statut', name: 'applications_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatus(
        Request $request,
        int $id,
        EmployerApplicationService $employerApplicationService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('employer_app_status_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $status = (string) $request->request->get('status', '');

        try {
            $employerApplicationService->updateStatus($user, $id, $status);
            $this->addFlash('success', 'Statut de la candidature mis à jour.');
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', 'Statut invalide.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $return = (string) $request->request->get('return', 'list');
        if ($return === 'profile') {
            return $this->redirectToRoute('app_employer_applications_profile', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_employer_applications', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/candidatures/{id}/lettre', name: 'applications_cover_letter', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function coverLetter(
        int $id,
        ApplicationsTableGateway $applicationsTableGateway,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $uid = $user->getId();
        if ($uid === null) {
            throw $this->createAccessDeniedException();
        }

        $candidature = $applicationsTableGateway->fetchEmployerCandidatureProfileForEmployer($id, (int) $uid);
        if ($candidature === null) {
            throw $this->createNotFoundException('Candidature introuvable.');
        }

        $letter = $candidature->coverLetter;
        if ($letter === null || trim($letter) === '') {
            throw $this->createNotFoundException('Aucune lettre de motivation pour cette candidature.');
        }

        return $this->render('recrutement/employer/applications/cover_letter.html.twig', [
            'candidature' => $candidature,
            'cover_letter' => $letter,
        ]);
    }

    #[Route('/candidatures/{id}/cv', name: 'applications_cv', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadCv(
        Request $request,
        int $id,
        ApplicationsTableGateway $applicationsTableGateway,
        EmployerApplicationService $employerApplicationService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $row = $applicationsTableGateway->fetchById($id);
        if ($row === null) {
            throw $this->createNotFoundException('Candidature introuvable.');
        }

        try {
            $employerApplicationService->assertEmployerManagesApplication($user, $id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $path = $this->resolveCvAbsolutePathFromRow($row);
        if ($path === null) {
            throw $this->createNotFoundException('Fichier CV introuvable.');
        }

        $response = new BinaryFileResponse($path);
        $inline = $request->query->get('inline') === '1';
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $isPdf = str_ends_with(strtolower($path), '.pdf') || $mime === 'application/pdf';
        $response->setContentDisposition(
            $inline && $isPdf ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($path),
        );

        return $response;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveCvAbsolutePathFromRow(array $row): ?string
    {
        $custom = $row['custom_cv_url'] ?? null;
        if (\is_string($custom) && trim($custom) !== '') {
            $t = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($custom, '/'));
            foreach ([
                $this->projectDir.DIRECTORY_SEPARATOR.$t,
                $this->projectDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$t,
            ] as $p) {
                if (is_file($p) && is_readable($p)) {
                    return $p;
                }
            }
        }

        $rel = $row['cv_path'] ?? '';
        $rel = \is_string($rel) ? $rel : '';
        if ($rel !== '') {
            $p = $this->cvUploadDir.DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel), DIRECTORY_SEPARATOR);
            if (is_file($p) && is_readable($p)) {
                return $p;
            }
        }

        return null;
    }
}

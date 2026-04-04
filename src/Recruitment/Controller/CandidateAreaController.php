<?php

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\ApplicationRowView;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Repository\JobInterviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Pages « mon espace » candidat (hors admin / employeur).
 */
#[Route('/mon-espace', name: 'app_candidate_area_')]
#[IsGranted('ROLE_USER')]
final class CandidateAreaController extends AbstractController
{
    #[Route('/candidatures', name: 'applications', methods: ['GET'])]
    public function myApplications(
        ApplicationsTableGateway $applicationsTableGateway,
        JobOfferRepository $jobOfferRepository,
        JobInterviewRepository $jobInterviewRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (\in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_employer_dashboard');
        }

        $uid = $user->getId();
        if ($uid === null) {
            return $this->render('recrutement/candidate/applications/index.html.twig', [
                'applications' => [],
            ]);
        }

        $rows = $applicationsTableGateway->fetchCandidateApplicationsWithJobAndCompany((int) $uid);
        $ids = array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $rows);
        $interviewsByAppId = $jobInterviewRepository->findIndexedByApplicationIds($ids);

        $applications = [];
        foreach ($rows as $row) {
            $aid = (int) ($row['id'] ?? 0);
            $jobOfferId = (int) ($row['job_offer_id'] ?? 0);
            $job = $jobOfferRepository->find($jobOfferId);
            $titleFromSql = \is_string($row['job_title'] ?? null) ? trim((string) $row['job_title']) : '';
            $displayTitle = $titleFromSql !== '' ? $titleFromSql : ($job?->getTitle() ?? ('Offre #'.$jobOfferId));
            $companyName = \is_string($row['company_name'] ?? null) && trim((string) $row['company_name']) !== ''
                ? trim((string) $row['company_name'])
                : null;
            $jobLocation = \is_string($row['job_location'] ?? null) && trim((string) $row['job_location']) !== ''
                ? trim((string) $row['job_location'])
                : null;

            $applications[] = [
                'application' => ApplicationRowView::forTwig($row),
                'display_title' => $displayTitle,
                'company_name' => $companyName,
                'job_location' => $jobLocation,
                'job' => $job,
                'interview' => $interviewsByAppId[$aid] ?? null,
            ];
        }

        return $this->render('recrutement/candidate/applications/index.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidatures/{id}/supprimer', name: 'applications_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteApplication(int $id, Request $request, ApplicationsTableGateway $applicationsTableGateway): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (\in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            throw $this->createAccessDeniedException();
        }

        $uid = $user->getId();
        if ($uid === null) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('delete_candidate_application_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée ou jeton invalide. Réessayez.');

            return $this->redirectToRoute('app_candidate_area_applications');
        }

        $ok = $applicationsTableGateway->deleteByIdForCandidate($id, (int) $uid);
        if ($ok) {
            $this->addFlash('success', 'Votre candidature a été supprimée.');
        } else {
            $this->addFlash('error', 'Impossible de supprimer cette candidature.');
        }

        return $this->redirectToRoute('app_candidate_area_applications');
    }

    /** Redirection : les entretiens sont affichés sur « Mes candidatures ». */
    #[Route('/entretiens', name: 'interviews', methods: ['GET'])]
    public function interviews(): Response
    {
        return $this->redirectToRoute('app_candidate_area_applications');
    }
}

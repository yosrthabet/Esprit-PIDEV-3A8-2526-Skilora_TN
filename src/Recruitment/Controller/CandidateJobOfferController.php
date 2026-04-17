<?php

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\ApplicationRowView;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Form\JobApplicationType;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Service\AnetiService;
use App\Recruitment\WorkTypeCatalog;
use App\Recruitment\Service\ApplicationSubmissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/offres', name: 'app_candidate_offres_')]
#[IsGranted('ROLE_USER')]
final class CandidateJobOfferController extends AbstractController
{
    public function __construct(
        private readonly JobOfferRepository $jobOfferRepository,
        private readonly ApplicationsTableGateway $applicationsTableGateway,
        private readonly AnetiService $anetiService,
        private readonly bool $employerSeesAllCandidatures = false,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $search = $request->query->get('q');
        $search = \is_string($search) ? trim($search) : null;
        if ($search === '') {
            $search = null;
        }

        $workTypeParam = $request->query->get('work_type');
        $workTypeParam = \is_string($workTypeParam) ? trim($workTypeParam) : '';
        $workType = WorkTypeCatalog::normalizeFilter($workTypeParam);

        $sortParam = $request->query->get('sort');
        $sort = (\is_string($sortParam) && $sortParam === 'salary') ? 'salary' : 'posted';

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 12;

        $jobOffers = $this->jobOfferRepository->findOpenOffersForCandidatesFiltered($search, $workType, $page, $perPage, $sort);
        $localTotal = $this->jobOfferRepository->countOpenOffersForCandidatesFiltered($search, $workType);
        $pageCount = max(1, (int) ceil($localTotal / $perPage));

        $anetiOffers = [];
        if ($page === 1) {
            $anetiOffers = $this->anetiService->fetchOffers($search, 8);
            $anetiOffers = $this->dedupeAnetiOffers($anetiOffers, $jobOffers);
        }

        $combinedOffers = array_merge($jobOffers, $anetiOffers);

        $workTypeForTemplate = 'all';
        if ($workTypeParam !== '' && $workTypeParam !== 'all') {
            $workTypeForTemplate = $workType ?? 'all';
        }

        return $this->render('recrutement/candidate/job_offer/index.html.twig', [
            'job_offers' => $combinedOffers,
            'is_employer' => \in_array('ROLE_EMPLOYER', $user->getRoles(), true),
            'search' => $search,
            'work_type' => $workTypeForTemplate,
            'sort' => $sort,
            'current_page' => $page,
            'page_count' => $pageCount,
            'total' => $localTotal,
            'aneti_count' => \count($anetiOffers),
            'per_page' => $perPage,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $anetiOffers
     * @param list<JobOffer> $localOffers
     *
     * @return list<array<string, mixed>>
     */
    private function dedupeAnetiOffers(array $anetiOffers, array $localOffers): array
    {
        $seen = [];
        foreach ($localOffers as $job) {
            $seen[$this->offerSignature(
                (string) $job->getTitle(),
                (string) ($job->getCompanyName() ?? $job->getCompany()?->getName() ?? ''),
                (string) ($job->getLocation() ?? ''),
            )] = true;
        }

        $out = [];
        foreach ($anetiOffers as $row) {
            $sig = $this->offerSignature(
                (string) ($row['title'] ?? ''),
                (string) ($row['companyName'] ?? ''),
                (string) ($row['location'] ?? ''),
            );
            if ($sig === '||' || isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;
            $out[] = $row;
        }

        return $out;
    }

    private function offerSignature(string $title, string $company, string $location): string
    {
        $norm = static fn (string $v): string => strtolower(trim(preg_replace('/\s+/u', ' ', $v) ?? $v));

        return $norm($title).'|'.$norm($company).'|'.$norm($location);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $jobOffer = $this->jobOfferRepository->findOpenForCandidateById($id);
        if ($jobOffer === null) {
            throw $this->createNotFoundException('Offre non disponible.');
        }

        $isEmployer = \in_array('ROLE_EMPLOYER', $user->getRoles(), true);
        $uid = $user->getId();
        $myApplication = null;
        $hasApplied = false;
        if ($uid !== null && !$isEmployer) {
            $row = $this->applicationsTableGateway->fetchByJobOfferAndCandidate($id, (int) $uid);
            if ($row !== null) {
                $hasApplied = true;
                $myApplication = ApplicationRowView::forTwig($row);
            }
        }

        return $this->render('recrutement/candidate/job_offer/show.html.twig', [
            'job_offer' => $jobOffer,
            'is_employer' => $isEmployer,
            'has_applied' => $hasApplied,
            'my_application' => $myApplication,
        ]);
    }

    #[Route('/{id}/postuler', name: 'apply', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function apply(
        Request $request,
        JobOffer $jobOffer,
        ApplicationSubmissionService $applicationSubmissionService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (strtoupper(trim($jobOffer->getStatus())) !== 'OPEN') {
            $this->addFlash('warning', 'Cette offre n’accepte plus de candidatures.');

            return $this->redirectToRoute('app_candidate_offres_show', ['id' => $jobOffer->getId()]);
        }

        if (\in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            $this->addFlash('warning', 'Les comptes employeur ne peuvent pas postuler ici.');

            return $this->redirectToRoute('app_candidate_offres_show', ['id' => $jobOffer->getId()]);
        }

        $jid = $jobOffer->getId();
        $uid = $user->getId();
        if ($jid !== null && $uid !== null
            && $this->applicationsTableGateway->existsForJobOfferAndCandidate($jid, $uid)) {
            $this->addFlash(
                'info',
                'Vous avez déjà postulé à cette offre. La lettre de motivation enregistrée à l’envoi '
                .'est visible dans « Mes candidatures » et sur la fiche de l’offre ; '
                .'elle ne peut pas être modifiée depuis le formulaire de postulation.',
            );

            return $this->redirectToRoute('app_candidate_offres_show', ['id' => $jobOffer->getId()]);
        }

        $form = $this->createForm(JobApplicationType::class);
        $form->handleRequest($request);
        $savedGeneratedCv = $request->getSession()->get('candidate_generated_cv_relpath');
        $hasSavedGeneratedCv = \is_string($savedGeneratedCv) && trim($savedGeneratedCv) !== '';

        if ($form->isSubmitted() && $form->isValid()) {
            $cv = $form->get('cv')->getData();
            $letter = $form->get('coverLetter')->getData();
            $useGeneratedCv = (bool) $form->get('useGeneratedCv')->getData();

            if (!$cv instanceof UploadedFile && !($useGeneratedCv && $hasSavedGeneratedCv)) {
                $this->addFlash('error', 'Veuillez joindre votre CV ou utiliser le CV généré.');

                return $this->render('recrutement/candidate/job_offer/apply.html.twig', [
                    'job_offer' => $jobOffer,
                    'form' => $form,
                    'has_saved_generated_cv' => $hasSavedGeneratedCv,
                ]);
            }

            try {
                if ($cv instanceof UploadedFile) {
                    $applicationSubmissionService->submit(
                        $user,
                        $jobOffer,
                        $cv,
                        \is_string($letter) ? $letter : null,
                    );
                } else {
                    \assert(\is_string($savedGeneratedCv));
                    $applicationSubmissionService->submitUsingExistingCvPath(
                        $user,
                        $jobOffer,
                        $savedGeneratedCv,
                        \is_string($letter) ? $letter : null,
                    );
                }
                $employerLabel = $jobOffer->getCompany()?->getName()
                    ?? $jobOffer->getCompanyName()
                    ?? 'l’entreprise liée à cette offre';
                if ($this->employerSeesAllCandidatures) {
                    $this->addFlash(
                        'success',
                        'Candidature enregistrée pour « '.$jobOffer->getTitle().' » ('.$employerLabel.'). '
                        .'Les comptes employeur voient les candidatures sur la liste « Candidatures ». '
                        .'Vous la retrouvez aussi dans « Mes candidatures ».',
                    );
                } else {
                    $this->addFlash(
                        'success',
                        'Candidature enregistrée pour « '.$jobOffer->getTitle().'». '
                        .'Elle est visible chez le recruteur de '.$employerLabel.' (comptes liés à cette entreprise). '
                        .'Vous la retrouvez dans « Mes candidatures ».',
                    );
                }

                return $this->redirectToRoute(
                    'app_candidate_area_applications',
                    [],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('recrutement/candidate/job_offer/apply.html.twig', [
            'job_offer' => $jobOffer,
            'form' => $form,
            'has_saved_generated_cv' => $hasSavedGeneratedCv,
        ]);
    }
}

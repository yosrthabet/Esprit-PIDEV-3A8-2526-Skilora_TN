<?php

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\ApplicationRowView;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Form\JobApplicationType;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\JobOfferRepository;
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

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 12;

        $jobOffers = $this->jobOfferRepository->findOpenOffersForCandidatesFiltered($search, $workType, $page, $perPage);
        $total = $this->jobOfferRepository->countOpenOffersForCandidatesFiltered($search, $workType);
        $pageCount = max(1, (int) ceil($total / $perPage));

        return $this->render('recrutement/candidate/job_offer/index.html.twig', [
            'job_offers' => $jobOffers,
            'is_employer' => \in_array('ROLE_EMPLOYER', $user->getRoles(), true),
            'search' => $search,
            'work_type' => $workTypeParam === '' ? 'all' : ($workType ?? 'all'),
            'current_page' => $page,
            'page_count' => $pageCount,
            'total' => $total,
            'per_page' => $perPage,
        ]);
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

        if ($jobOffer->getStatus() !== 'OPEN') {
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
            $this->addFlash('info', 'Vous avez déjà postulé à cette offre.');

            return $this->redirectToRoute('app_candidate_offres_show', ['id' => $jobOffer->getId()]);
        }

        $form = $this->createForm(JobApplicationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cv = $form->get('cv')->getData();
            $letter = $form->get('coverLetter')->getData();

            if (!$cv instanceof UploadedFile) {
                $this->addFlash('error', 'Veuillez joindre votre CV.');

                return $this->render('recrutement/candidate/job_offer/apply.html.twig', [
                    'job_offer' => $jobOffer,
                    'form' => $form,
                ]);
            }

            try {
                $applicationSubmissionService->submit(
                    $user,
                    $jobOffer,
                    $cv,
                    \is_string($letter) ? $letter : null,
                );
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
                    'app_candidate_offres_show',
                    ['id' => $jobOffer->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('recrutement/candidate/job_offer/apply.html.twig', [
            'job_offer' => $jobOffer,
            'form' => $form,
        ]);
    }
}

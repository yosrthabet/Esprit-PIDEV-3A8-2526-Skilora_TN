<?php

declare(strict_types=1);

namespace App\Recruitment\Controller;

use App\Controller\AppController;
use App\Entity\User;
use App\Enum\ApplicationStatus;
use App\Enum\Currency;
use App\Enum\ExperienceLevel;
use App\Enum\JobOfferStatus;
use App\Enum\WorkType;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\HireOffer;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Entity\JobPreference;
use App\Recruitment\Entity\SavedJob;
use App\Recruitment\Repository\ApplicationRepository;
use App\Recruitment\Repository\CompanyRepository;
use App\Recruitment\Repository\HireOfferRepository;
use App\Recruitment\Repository\JobInterviewRepository;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Repository\JobPreferenceRepository;
use App\Recruitment\Repository\SavedJobRepository;
use App\Recruitment\Service\ApplicationSubmissionService;
use App\Recruitment\Service\ApplicationPipelineService;
use App\Recruitment\Service\EmployerJobOfferService;
use App\Recruitment\Service\JobMatchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RecruitmentController extends AppController
{
    public function __construct(
        private readonly JobOfferRepository $jobOfferRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly SavedJobRepository $savedJobRepository,
        private readonly JobPreferenceRepository $jobPreferenceRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly JobInterviewRepository $jobInterviewRepository,
        private readonly HireOfferRepository $hireOfferRepository,
        private readonly JobMatchService $jobMatchService,
        private readonly EmployerJobOfferService $employerJobOfferService,
        private readonly ApplicationSubmissionService $applicationSubmissionService,
        private readonly ApplicationPipelineService $applicationPipelineService,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $cvUploadDir,
    ) {
    }

    #[Route('/recruitment', name: 'app_recruitment', methods: ['GET'])]
    public function hub(): Response
    {
        return $this->redirectToRoute('app_jobs');
    }

    #[Route('/jobs', name: 'app_jobs', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function jobs(Request $request): Response
    {
        $query = trim($request->query->getString('q')) ?: null;
        $workType = $this->normalizeWorkType($request->query->getString('work_type'));
        $source = $this->normalizeSource($request->query->getString('source'));
        $sort = in_array($request->query->getString('sort'), ['fresh', 'salary', 'match'], true) ? $request->query->getString('sort') : 'match';
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 12;
        $sourceCounts = $this->jobOfferRepository->countOpenBySource($query, $workType);
        $jobs = $this->jobOfferRepository->findOpenForDiscovery($query, $workType, $source, $sort, $perPage, ($page - 1) * $perPage);
        $total = $source !== null ? ($sourceCounts[$source] ?? 0) : $sourceCounts['all'];
        $user = $this->getAppUser();

        return $this->render('recruitment/jobs/index.html.twig', [
            'jobs' => $jobs,
            'match_cards' => array_map(fn (JobOffer $job) => ['job' => $job, 'match' => $this->jobMatchService->score($user, $job)], $jobs),
            'q' => $query,
            'work_type' => $workType ?? 'all',
            'source' => $source ?? 'all',
            'sort' => $sort,
            'page' => $page,
            'page_count' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'source_counts' => $sourceCounts,
        ]);
    }

    #[Route('/jobs/{id}', name: 'app_job_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(JobOffer $jobOffer): Response
    {
        $user = $this->getAppUser();
        $jobOffer->incrementViews();
        $this->entityManager->flush();

        return $this->render('recruitment/jobs/show.html.twig', [
            'job' => $jobOffer,
            'match' => $this->jobMatchService->score($user, $jobOffer),
            'has_applied' => $this->applicationRepository->existsForUserAndJob($user, $jobOffer),
            'is_saved' => $this->savedJobRepository->isSaved($user, $jobOffer),
        ]);
    }

    #[Route('/jobs/{id}/apply', name: 'app_job_apply', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[Route('/offres/{id}/postuler', name: 'app_candidate_job_apply', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function apply(Request $request, JobOffer $jobOffer): Response
    {
        $user = $this->getAppUser();
        if ($request->isMethod('GET')) {
            return $this->render('recruitment/jobs/apply.html.twig', [
                'job' => $jobOffer,
                'match' => $this->jobMatchService->score($user, $jobOffer),
                'has_applied' => $this->applicationRepository->existsForUserAndJob($user, $jobOffer),
                'saved_cv_relpath' => $request->getSession()->get('candidate_generated_cv_relpath'),
            ]);
        }

        if (!$this->isCsrfTokenValid('apply_job_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            if (!$this->applicationRepository->existsForUserAndJob($user, $jobOffer)) {
                $cv = $request->files->get('cv');
                $coverLetter = $request->request->getString('cover_letter');
                if ($request->request->getBoolean('use_generated_cv')) {
                    $savedCv = $request->getSession()->get('candidate_generated_cv_relpath');
                    if (!is_string($savedCv) || trim($savedCv) === '') {
                        throw new \RuntimeException('No generated CV is available. Create one or upload a CV file.');
                    }
                    $this->applicationSubmissionService->submitUsingExistingCvPath($user, $jobOffer, $savedCv, $coverLetter);
                } else {
                    $this->applicationSubmissionService->submit($user, $jobOffer, $cv instanceof UploadedFile ? $cv : null, $coverLetter);
                }
                $this->addFlash('success', 'Application sent. Track it from your pipeline.');
            } else {
                $this->addFlash('info', 'You already applied to this job.');
            }
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_job_apply', ['id' => $jobOffer->getId()]);
        }

        return $this->redirectToRoute('app_applications');
    }

    #[Route('/jobs/{id}/save', name: 'app_job_save', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function save(Request $request, JobOffer $jobOffer): Response
    {
        $user = $this->getAppUser();
        if (!$this->isCsrfTokenValid('save_job_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$this->savedJobRepository->isSaved($user, $jobOffer)) {
            $this->entityManager->persist((new SavedJob())->setUser($user)->setJobOffer($jobOffer));
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_job_show', ['id' => $jobOffer->getId()]);
    }

    #[Route('/applications', name: 'app_applications', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function applications(): Response
    {
        $user = $this->getAppUser();
        if (strtoupper($user->getRole() ?? '') === 'EMPLOYER') {
            $company = $this->companyRepository->findOneForOwner($user);
            return $this->render('recruitment/employer/applications.html.twig', [
                'company' => $company,
                'applications' => $company ? $this->applicationRepository->findForCompany($company) : [],
            ]);
        }

        $interviewsByApplication = [];
        foreach ($this->jobInterviewRepository->findForCandidate($user) as $interview) {
            $applicationId = $interview->getApplication()->getId();
            if ($applicationId !== null) {
                $interviewsByApplication[$applicationId] = $interview;
            }
        }
        $offersByApplication = [];
        foreach ($this->hireOfferRepository->findForCandidate($user) as $offer) {
            $applicationId = $offer->getApplication()->getId();
            if ($applicationId !== null) {
                $offersByApplication[$applicationId] = $offer;
            }
        }

        return $this->render('recruitment/applications/index.html.twig', [
            'applications' => $this->applicationRepository->findForCandidate($user),
            'interviews_by_application' => $interviewsByApplication,
            'offers_by_application' => $offersByApplication,
        ]);
    }

    #[Route('/applications/{id}', name: 'app_application_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function applicationShow(Application $application): Response
    {
        $user = $this->getAppUser();
        if (strtoupper($user->getRole() ?? '') === 'EMPLOYER') {
            $this->applicationPipelineService->assertEmployerManages($user, $application);
        } elseif ($application->getCandidate()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('recruitment/applications/show.html.twig', [
            'application' => $application,
            'interview' => $this->jobInterviewRepository->findOneForApplication($application),
            'hire_offer' => $this->hireOfferRepository->findOneForApplication($application),
        ]);
    }

    #[Route('/applications/{id}/cv', name: 'app_application_cv', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function applicationCv(Request $request, Application $application): BinaryFileResponse
    {
        $user = $this->getAppUser();
        if (strtoupper($user->getRole() ?? '') === 'EMPLOYER') {
            $this->applicationPipelineService->assertEmployerManages($user, $application);
        } elseif ($application->getCandidate()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->buildApplicationCvResponse($request, $application);
    }

    #[Route('/employer/candidatures/{id}/cv', name: 'app_employer_applications_cv', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function employerApplicationCv(Request $request, Application $application): BinaryFileResponse
    {
        $this->applicationPipelineService->assertEmployerManages($this->getAppUser(), $application);

        return $this->buildApplicationCvResponse($request, $application);
    }

    #[Route('/applications/{id}/profile', name: 'app_application_profile', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Route('/employer/candidatures/{id}/profil', name: 'app_employer_applications_profile', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function applicationProfile(Application $application): Response
    {
        $this->applicationPipelineService->assertEmployerManages($this->getAppUser(), $application);

        return $this->render('recruitment/employer/profile.html.twig', [
            'application' => $application,
            'interview' => $this->jobInterviewRepository->findOneForApplication($application),
            'hire_offer' => $this->hireOfferRepository->findOneForApplication($application),
        ]);
    }

    #[Route('/employer/candidatures/{id}/lettre', name: 'app_employer_application_cover_letter', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function applicationCoverLetter(Application $application): Response
    {
        $this->applicationPipelineService->assertEmployerManages($this->getAppUser(), $application);
        if (trim($application->getCoverLetter() ?? '') === '') {
            throw $this->createNotFoundException('No cover letter attached to this application.');
        }

        return $this->render('recruitment/employer/profile.html.twig', [
            'application' => $application,
            'interview' => $this->jobInterviewRepository->findOneForApplication($application),
            'hire_offer' => $this->hireOfferRepository->findOneForApplication($application),
            'cover_letter_focus' => true,
        ]);
    }

    private function buildApplicationCvResponse(Request $request, Application $application): BinaryFileResponse
    {
        $cvPath = $application->getCvPath();
        if ($cvPath === null) {
            throw $this->createNotFoundException('No CV attached to this application.');
        }
        $path = rtrim($this->cvUploadDir, '/\\') . '/' . ltrim($cvPath, '/\\');
        if (!is_file($path)) {
            throw $this->createNotFoundException('CV file not found.');
        }

        $response = new BinaryFileResponse($path);
        $disposition = $request->query->getBoolean('inline') ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT;
        $response->setContentDisposition($disposition, 'application-' . $application->getId() . '-cv.' . pathinfo($path, PATHINFO_EXTENSION));

        return $response;
    }

    #[Route('/job-preferences', name: 'app_candidate_job_preferences', methods: ['GET', 'POST'])]
    #[Route('/mon-espace/preferences-emploi', name: 'app_candidate_preferences_old', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function preferences(Request $request): Response
    {
        $user = $this->getAppUser();
        if (strtoupper($user->getRole() ?? '') === 'EMPLOYER') {
            throw $this->createAccessDeniedException();
        }
        $preferences = $this->jobPreferenceRepository->findOneForUser($user) ?? (new JobPreference())->setUser($user);
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('job_preferences_' . $user->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $preferences
                ->setTargetTitle(trim($request->request->getString('target_title')) ?: null)
                ->setLocation(trim($request->request->getString('location')) ?: null)
                ->setWorkType(WorkType::tryFrom($request->request->getString('work_type')))
                ->setMinSalary($request->request->getString('min_salary') !== '' ? max(0, $request->request->getInt('min_salary')) : null);
            $this->entityManager->persist($preferences);
            $this->entityManager->flush();
            $this->addFlash('success', 'Job preferences saved.');

            return $this->redirectToRoute('app_candidate_job_preferences');
        }

        return $this->render('recruitment/preferences/index.html.twig', [
            'preferences' => $preferences,
            'work_types' => WorkType::cases(),
        ]);
    }

    #[Route('/applications/{id}/status', name: 'app_application_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function applicationStatus(Request $request, Application $application): Response
    {
        if (!$this->isCsrfTokenValid('application_status_' . $application->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $status = ApplicationStatus::tryFrom($request->request->getString('status')) ?? ApplicationStatus::VIEWED;
        $this->applicationPipelineService->move($this->getAppUser(), $application, $status);

        return $this->redirectToRoute('app_application_show', ['id' => $application->getId()]);
    }

    #[Route('/applications/{id}/interview', name: 'app_application_interview', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function applicationInterview(Request $request, Application $application): Response
    {
        $user = $this->getAppUser();
        $this->applicationPipelineService->assertEmployerManages($user, $application);
        $interview = $this->jobInterviewRepository->findOneForApplication($application);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('schedule_interview_' . $application->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $interview = $this->applicationPipelineService->scheduleInterview($user, $application, $request->request);

            return $this->redirectToRoute('app_application_show', ['id' => $application->getId()]);
        }

        return $this->render('recruitment/employer/interview_form.html.twig', [
            'application' => $application,
            'interview' => $interview,
        ]);
    }

    #[Route('/applications/{id}/offer', name: 'app_application_offer', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function applicationOffer(Request $request, Application $application): Response
    {
        $user = $this->getAppUser();
        $this->applicationPipelineService->assertEmployerManages($user, $application);
        $offer = $this->hireOfferRepository->findOneForApplication($application);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('send_offer_' . $application->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $offer = $this->applicationPipelineService->sendHireOffer($user, $application, $request->request);

            return $this->redirectToRoute('app_application_show', ['id' => $application->getId()]);
        }

        return $this->render('recruitment/employer/offer_form.html.twig', [
            'application' => $application,
            'offer' => $offer,
        ]);
    }

    #[Route('/hire-offers', name: 'app_hire_offers', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function hireOffers(): Response
    {
        return $this->render('recruitment/offers/index.html.twig', [
            'offers' => $this->hireOfferRepository->findForCandidate($this->getAppUser()),
        ]);
    }

    #[Route('/hire-offers/{id}/accept', name: 'app_hire_offer_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function acceptHireOffer(Request $request, HireOffer $hireOffer): Response
    {
        if (!$this->isCsrfTokenValid('accept_offer_' . $hireOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->applicationPipelineService->acceptOffer($this->getAppUser(), $hireOffer);
        $this->addFlash('success', 'Offer accepted. Finance contract setup is next.');

        return $this->redirectToRoute('app_hire_offers');
    }

    #[Route('/hire-offers/{id}/reject', name: 'app_hire_offer_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function rejectHireOffer(Request $request, HireOffer $hireOffer): Response
    {
        if (!$this->isCsrfTokenValid('reject_offer_' . $hireOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->applicationPipelineService->rejectOffer($this->getAppUser(), $hireOffer);

        return $this->redirectToRoute('app_hire_offers');
    }

    #[Route('/post-job', name: 'app_post_job', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function postJob(Request $request): Response
    {
        $user = $this->getAppUser();
        $company = $this->employerJobOfferService->getOrCreateCompany($user);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('post_job', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $job = $this->employerJobOfferService->createFromRequest($user, $request->request);

            return $this->redirectToRoute('app_employer_offer_show', ['id' => $job->getId()]);
        }

        return $this->render('recruitment/employer/post_job.html.twig', ['company' => $company, 'job' => null]);
    }

    #[Route('/active-offers', name: 'app_active_offers', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function activeOffers(): Response
    {
        $company = $this->companyRepository->findOneForOwner($this->getAppUser());

        return $this->render('recruitment/employer/offers.html.twig', [
            'company' => $company,
            'jobs' => $company ? $this->jobOfferRepository->findRecentForCompany($company, 50) : [],
        ]);
    }

    #[Route('/active-offers/{id}', name: 'app_employer_offer_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function showEmployerOffer(JobOffer $jobOffer): Response
    {
        $this->employerJobOfferService->assertOwner($this->getAppUser(), $jobOffer);

        return $this->render('recruitment/employer/show.html.twig', [
            'job' => $jobOffer,
            'applications' => $jobOffer->getCompany() ? $this->applicationRepository->findForCompany($jobOffer->getCompany()) : [],
        ]);
    }

    #[Route('/active-offers/{id}/edit', name: 'app_employer_offer_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function editEmployerOffer(Request $request, JobOffer $jobOffer): Response
    {
        $user = $this->getAppUser();
        $this->employerJobOfferService->assertOwner($user, $jobOffer);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_job_' . $jobOffer->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $this->employerJobOfferService->updateFromRequest($user, $jobOffer, $request->request);

            return $this->redirectToRoute('app_employer_offer_show', ['id' => $jobOffer->getId()]);
        }

        return $this->render('recruitment/employer/post_job.html.twig', ['company' => $jobOffer->getCompany(), 'job' => $jobOffer]);
    }

    #[Route('/active-offers/{id}/close', name: 'app_employer_offer_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function closeEmployerOffer(Request $request, JobOffer $jobOffer): Response
    {
        if (!$this->isCsrfTokenValid('close_job_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->employerJobOfferService->close($this->getAppUser(), $jobOffer);

        return $this->redirectToRoute('app_employer_offer_show', ['id' => $jobOffer->getId()]);
    }

    #[Route('/active-offers/{id}/reopen', name: 'app_employer_offer_reopen', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function reopenEmployerOffer(Request $request, JobOffer $jobOffer): Response
    {
        if (!$this->isCsrfTokenValid('reopen_job_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->employerJobOfferService->reopen($this->getAppUser(), $jobOffer);

        return $this->redirectToRoute('app_employer_offer_show', ['id' => $jobOffer->getId()]);
    }

    #[Route('/active-offers/{id}/duplicate', name: 'app_employer_offer_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function duplicateEmployerOffer(Request $request, JobOffer $jobOffer): Response
    {
        if (!$this->isCsrfTokenValid('duplicate_job_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $copy = $this->employerJobOfferService->duplicate($this->getAppUser(), $jobOffer);

        return $this->redirectToRoute('app_employer_offer_edit', ['id' => $copy->getId()]);
    }

    #[Route('/active-offers/{id}/delete', name: 'app_employer_offer_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYER')]
    public function deleteEmployerOffer(Request $request, JobOffer $jobOffer): Response
    {
        if (!$this->isCsrfTokenValid('delete_job_' . $jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->employerJobOfferService->delete($this->getAppUser(), $jobOffer);

        return $this->redirectToRoute('app_active_offers');
    }

    #[Route('/interviews', name: 'app_interviews', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function interviews(): Response
    {
        return $this->render('recruitment/interviews/index.html.twig');
    }

    #[Route('/search/jobs', name: 'app_job_search_api', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchApi(Request $request): JsonResponse
    {
        $query = trim($request->query->getString('q'));
        if (mb_strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }

        $user = $this->getAppUser();
        $results = array_map(function (JobOffer $job) use ($user): array {
            $match = $this->jobMatchService->score($user, $job);
            $source = $job->getFeedSource();
            return [
                'title' => $job->getTitle(),
                'company' => $job->getCompanyLabel(),
                'location' => $job->getLocation(),
                'score' => $match['score'],
                'source' => $source !== null ? $source->value : 'platform',
                'url' => $this->generateUrl('app_job_show', ['id' => $job->getId()]),
            ];
        }, $this->jobOfferRepository->findSearchSuggestions($query));

        return new JsonResponse(['results' => $results]);
    }

    private function normalizeWorkType(string $workType): ?string
    {
        return WorkType::tryFrom($workType)?->value;
    }

    private function normalizeSource(string $source): ?string
    {
        return in_array($source, ['platform', 'aneti', 'reddit', 'rss', 'linkedin_rss'], true) ? $source : null;
    }
}

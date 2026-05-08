<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\Repository\ApplicationRepository;
use App\Recruitment\Repository\CompanyRepository;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Repository\SavedJobRepository;

class RecruitmentDashboardService
{
    public function __construct(
        private readonly JobOfferRepository $jobOfferRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly SavedJobRepository $savedJobRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly JobMatchService $jobMatchService,
    ) {
    }

    /**
     * @param list<string>|null $skillNames
     * @return array<string, mixed>
     */
    public function forUser(User $user, ?array $skillNames = null): array
    {
        if (strtoupper($user->getRole() ?? '') === 'EMPLOYER') {
            return $this->forEmployer($user);
        }

        $skillNames ??= $this->jobMatchService->getSkillNames($user);
        $recommended = $this->jobOfferRepository->findRecommendedForUser($user, $skillNames, 5);
        $applications = $this->applicationRepository->findForCandidate($user);
        $saved = $this->savedJobRepository->findForUser($user);

        return [
            'mode' => 'freelancer',
            'recommended_jobs' => array_map(fn ($job) => [
                'job' => $job,
                'match' => $this->jobMatchService->score($user, $job, $skillNames),
            ], $recommended),
            'applications_count' => count($applications),
            'saved_count' => count($saved),
            'radar' => [
                'fresh' => $this->jobOfferRepository->countOpenForDiscovery(null, null),
                'remote' => $this->jobOfferRepository->countOpenForDiscovery(null, 'remote'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function forEmployer(User $user): array
    {
        $company = $this->companyRepository->findOneForOwner($user);
        if ($company === null) {
            return ['mode' => 'employer', 'company' => null, 'open_jobs' => 0, 'recent_jobs' => [], 'applications' => []];
        }

        return [
            'mode' => 'employer',
            'company' => $company,
            'open_jobs' => $this->jobOfferRepository->countOpenForCompany($company),
            'recent_jobs' => $this->jobOfferRepository->findRecentForCompany($company, 5),
            'applications' => $this->applicationRepository->findForCompany($company, 6),
        ];
    }
}

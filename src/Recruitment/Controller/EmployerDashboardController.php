<?php

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\JobOfferRepository;
use App\Repository\UserRepository;
use App\Recruitment\Service\EmployerContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employer', name: 'app_employer_')]
#[IsGranted('ROLE_EMPLOYER')]
final class EmployerDashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(
        EmployerContext $employerContext,
        JobOfferRepository $jobOfferRepository,
        ApplicationsTableGateway $applicationsTableGateway,
        UserRepository $userRepository,
    ): Response {
        $principal = $this->getUser();
        if (!$principal instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $user = $userRepository->find($principal->getId()) ?? $principal;
        $company = $employerContext->getCompanyForEmployer($user);

        $openCount = 0;
        $totalCount = 0;
        $recentOffers = [];
        $applicationsCount = 0;
        $recentCandidatures = [];

        $uid = $user->getId();
        if ($company !== null) {
            $openCount = $jobOfferRepository->countOpenByCompany($company);
            $all = $jobOfferRepository->findByCompanyOrdered($company);
            $totalCount = \count($all);
            $recentOffers = \array_slice($all, 0, 5);
        }

        if ($uid !== null) {
            $applicationsCount = $applicationsTableGateway->countByEmployerOwnerUserId((int) $uid);
            $allC = $applicationsTableGateway->fetchEmployerCandidatureListForDisplay((int) $uid);
            $recentCandidatures = \array_slice($allC, 0, 6);
        }

        return $this->render('recrutement/employer/dashboard/index.html.twig', [
            'company' => $company,
            'open_job_count' => $openCount,
            'total_job_count' => $totalCount,
            'recent_job_offers' => $recentOffers,
            'applications_count' => $applicationsCount,
            'recent_candidatures' => $recentCandidatures,
        ]);
    }
}

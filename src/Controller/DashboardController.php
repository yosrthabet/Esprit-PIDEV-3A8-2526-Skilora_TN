<?php

namespace App\Controller;

use App\Entity\User;
use App\Formation\Repository\CertificateRepository;
use App\Formation\Repository\EnrollmentRepository;
use App\Formation\Repository\FormationRepository;
use App\Repository\ProfileRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Recruitment\Service\RecruitmentDashboardService;
use App\Service\User\ProfileCompletionService;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function adminDashboard(UserRepository $userRepository, FormationRepository $formationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {
            return match (strtoupper($user->getRole() ?? '')) {
                'EMPLOYER' => $this->redirectToRoute('app_workspace'),
                'TRAINER'  => $this->redirectToRoute('app_trainer_dashboard'),
                default    => $this->redirectToRoute('app_workspace'),
            };
        }

        $recentUsers = [];
        foreach ($userRepository->getRecentUsers(5) as $u) {
            $recentUsers[] = [
                'name'   => $u->getDisplayName() ?? $u->getUsername(),
                'email'  => $u->getEmail(),
                'role'   => $u->getRoleDisplayName() ?? $u->getRole(),
                'status' => $u->isActive() ? 'Active' : 'Inactive',
            ];
        }

        return $this->render('dashboard/admin.html.twig', [
            'stats' => [
                ['label' => 'Utilisateurs', 'value' => (string) $userRepository->countAll(), 'change' => '', 'trend' => 'up'],
                ['label' => 'Actifs', 'value' => (string) $userRepository->countActiveAccounts(), 'change' => '', 'trend' => 'up'],
                ['label' => 'Formations', 'value' => (string) $formationRepository->countAll(), 'change' => '', 'trend' => 'up'],
            ],
            'recent_users' => $recentUsers,
        ]);
    }

    #[Route('/workspace', name: 'app_workspace')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function workspace(
        ProfileCompletionService $profileCompletionService,
        ProfileRepository $profileRepository,
        SkillRepository $skillRepository,
        RecruitmentDashboardService $recruitmentDashboardService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $isEmployer = strtoupper($user->getRole() ?? '') === 'EMPLOYER';
        $profile = null;
        $skillNames = [];

        if (!$isEmployer) {
            $profile = $profileRepository->findOneBy(['user' => $user]);
            if ($profile !== null) {
                try {
                    foreach ($skillRepository->findBy(['profile' => $profile]) as $skill) {
                        $name = mb_strtolower(trim($skill->getSkillName() ?? ''));
                        if ($name !== '') {
                            $skillNames[] = $name;
                        }
                    }
                } catch (TableNotFoundException) {
                    $skillNames = [];
                }
            }
        }

        $profileData = $isEmployer
            ? ['percentage' => 100, 'steps' => []]
            : $profileCompletionService->compute($user, $profile, count($skillNames));
        $recruitment = $recruitmentDashboardService->forUser($user, $skillNames);

        return $this->render('dashboard/freelancer.html.twig', [
            'profile_completion' => $profileData['percentage'],
            'profile_steps'      => $profileData['steps'],
            'stats'              => [],
            'in_progress'        => [],
            'enrollments'        => [],
            'applications_count' => 0,
            'latest_formations'  => [],
            'wallet'             => null,
            'progress_data'      => [],
            'enrolled_count'     => 0,
            'completed_count'    => 0,
            'certificate_count'  => 0,
            'contract_count'     => 0,
            'average_rating'     => null,
            'review_count'       => 0,
            'featured_jobs'      => [],
            'recruitment'        => $recruitment,
        ]);
    }

    #[Route('/trainer', name: 'app_trainer_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function trainerDashboard(
        FormationRepository $formationRepository,
        EnrollmentRepository $enrollmentRepository,
        CertificateRepository $certificateRepository,
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('dashboard/trainer.html.twig', [
            'stats' => [
                ['label' => 'Formations', 'value' => (string) $formationRepository->countForTrainer($user), 'hint' => 'courses created'],
                ['label' => 'Students', 'value' => (string) $enrollmentRepository->countStudentsForTrainer($user), 'hint' => 'unique learners'],
                ['label' => 'Certificates', 'value' => (string) $certificateRepository->countForTrainer($user), 'hint' => 'issued certificates'],
            ],
            'my_formations' => $formationRepository->findRecentForTrainer($user, 6),
            'my_course_count' => $formationRepository->countForTrainer($user),
            'total_students' => $enrollmentRepository->countStudentsForTrainer($user),
            'certificate_count' => $certificateRepository->countForTrainer($user),
            'enrollment_counts' => [],
        ]);
    }
}

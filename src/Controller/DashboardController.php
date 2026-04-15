<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CertificateRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function adminDashboard(
        UserRepository $userRepository,
        FormationRepository $formationRepository,
    ): Response {
        // Redirect non-admin users to their own dashboard
        if (!$this->isGranted('ROLE_ADMIN')) {
            /** @var User $user */
            $user = $this->getUser();
            return match (strtoupper($user->getRole() ?? '')) {
                'EMPLOYER' => $this->redirectToRoute('app_employer_dashboard'),
                'TRAINER' => $this->redirectToRoute('app_trainer_dashboard'),
                default => $this->redirectToRoute('app_workspace'),
            };
        }
        $totalUsers = $userRepository->countAll();
        $totalFormations = $formationRepository->countAll();

        $recentUsers = [];
        foreach ($userRepository->getRecentUsers(5) as $u) {
            $recentUsers[] = [
                'name' => $u->getDisplayName() ?? $u->getUsername(),
                'email' => $u->getEmail(),
                'role' => $u->getRoleDisplayName() ?? $u->getRole(),
                'status' => $u->isActive() ? 'Active' : 'Inactive',
            ];
        }

        return $this->render('dashboard/admin.html.twig', [
            'stats' => [
                ['label' => 'Total Users', 'value' => (string) $totalUsers, 'change' => '+12.5%', 'trend' => 'up'],
                ['label' => 'Active Jobs', 'value' => '143', 'change' => '+3.2%', 'trend' => 'up'],
                ['label' => 'Formations', 'value' => (string) $totalFormations, 'change' => '+7.1%', 'trend' => 'up'],
                ['label' => 'Revenue', 'value' => '24,500 TND', 'change' => '-2.4%', 'trend' => 'down'],
            ],
            'recent_users' => $recentUsers,
        ]);
    }

    #[Route('/workspace', name: 'app_workspace')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function freelancerWorkspace(
        FormationRepository $formationRepository,
        EnrollmentRepository $enrollmentRepository,
        CertificateRepository $certificateRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $totalFormations = $formationRepository->countAll();

        $enrollments = $enrollmentRepository->findByUserOrdered($user);
        $certificates = $certificateRepository->findByUserOrdered($user);
        $enrolledCount = count($enrollments);
        $completedCount = 0;
        $inProgress = [];
        foreach ($enrollments as $e) {
            if ($e->isCompleted()) {
                $completedCount++;
            } else {
                $inProgress[] = $e;
            }
        }

        $latestFormations = $formationRepository->findLatest(4);

        return $this->render('dashboard/freelancer.html.twig', [
            'stats' => [
                ['label' => 'Available', 'value' => (string) $totalFormations, 'icon' => 'book-open', 'color' => 'indigo'],
                ['label' => 'Enrolled', 'value' => (string) $enrolledCount, 'icon' => 'bookmark', 'color' => 'sky'],
                ['label' => 'In Progress', 'value' => (string) count($inProgress), 'icon' => 'play-circle', 'color' => 'amber'],
                ['label' => 'Completed', 'value' => (string) $completedCount, 'icon' => 'check-circle', 'color' => 'emerald'],
            ],
            'in_progress' => $inProgress,
            'enrollments' => $enrollments,
            'certificates' => $certificates,
            'latest_formations' => $latestFormations,
        ]);
    }

    // Employer dashboard is now handled by App\Recruitment\Controller\EmployerDashboardController

    #[Route('/trainer', name: 'app_trainer_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function trainerDashboard(
        FormationRepository $formationRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $totalFormations = $formationRepository->countAll();

        return $this->render('dashboard/trainer.html.twig', [
            'stats' => [
                ['label' => 'My Courses', 'value' => '0', 'icon' => 'graduation-cap', 'color' => 'indigo'],
                ['label' => 'Total Students', 'value' => '0', 'icon' => 'users', 'color' => 'sky'],
                ['label' => 'Certificates', 'value' => '0', 'icon' => 'award', 'color' => 'amber'],
                ['label' => 'Platform Courses', 'value' => (string) $totalFormations, 'icon' => 'book-open', 'color' => 'emerald'],
            ],
        ]);
    }
}

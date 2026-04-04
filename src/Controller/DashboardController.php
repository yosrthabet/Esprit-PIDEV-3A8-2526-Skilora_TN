<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FormationRepository;
use App\Service\DashboardDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly DashboardDataProvider $dashboardDataProvider,
    ) {
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'formation_count' => $this->formationRepository->countAll(),
            'recent_formations' => $this->formationRepository->findLatest(5),
        ]);
    }

    #[Route('/user', name: 'app_user_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function user(): Response
    {
        return $this->render('dashboard/index.html.twig', array_merge(
            $this->dashboardDataProvider->getOverview(),
            ['dashboard_area' => 'User'],
        ));
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render(
            'dashboard/index.html.twig',
            $this->dashboardDataProvider->getOverview(),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FormationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly FormationRepository $formationRepository,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $members = $this->userRepository->countAll();
        $courses = $this->formationRepository->countAll();

        return $this->render('home/index.html.twig', [
            'stats' => [
                [
                    'value' => (string) max(0, $members),
                    'label' => 'Membres',
                    'suffix' => 'Inscrits sur la plateforme',
                ],
                [
                    'value' => (string) max(0, $courses),
                    'label' => 'Formations',
                    'suffix' => 'Programmes au catalogue',
                ],
                [
                    'value' => '∞',
                    'label' => 'Opportunités',
                    'suffix' => 'Talents & entreprises',
                ],
                [
                    'value' => '24/7',
                    'label' => 'Disponible',
                    'suffix' => 'Où que vous soyez',
                ],
            ],
        ]);
    }
}

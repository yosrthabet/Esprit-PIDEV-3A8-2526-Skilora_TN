<?php

namespace App\Recruitment\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employer', name: 'app_employer_')]
#[IsGranted('ROLE_EMPLOYER')]
final class EmployerPlaceholderController extends AbstractController
{
    #[Route('/profil', name: 'profile', methods: ['GET'])]
    public function profile(): Response
    {
        return $this->render('recruitment/employer/placeholder.html.twig', [
            'title' => 'Mon profil',
            'message' => 'Ce module sera disponible prochainement.',
        ]);
    }
}

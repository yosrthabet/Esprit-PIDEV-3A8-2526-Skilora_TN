<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'stats' => [
                ['value' => '10K+', 'label' => 'Active Job Seekers', 'suffix' => 'Talent ready to work'],
                ['value' => '2,500', 'label' => 'Companies Hiring', 'suffix' => 'Verified Businesses'],
                ['value' => '500+', 'label' => 'Training Courses', 'suffix' => 'To boost skills'],
                ['value' => '$2M+', 'label' => 'Earned by Talent', 'suffix' => 'Paid securely'],
            ],
        ]);
    }
}

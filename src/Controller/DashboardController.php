<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                ['label' => 'Total Users', 'value' => '2,847', 'change' => '+12.5%', 'trend' => 'up'],
                ['label' => 'Active Jobs', 'value' => '143', 'change' => '+3.2%', 'trend' => 'up'],
                ['label' => 'Formations', 'value' => '38', 'change' => '+7.1%', 'trend' => 'up'],
                ['label' => 'Revenue', 'value' => '24,500 TND', 'change' => '-2.4%', 'trend' => 'down'],
            ],
            'recent_users' => [
                ['name' => 'Ahmed Ben Ali', 'email' => 'ahmed@example.com', 'role' => 'Job Seeker', 'status' => 'Active'],
                ['name' => 'Fatma Trabelsi', 'email' => 'fatma@example.com', 'role' => 'Employer', 'status' => 'Active'],
                ['name' => 'Youssef Gharbi', 'email' => 'youssef@example.com', 'role' => 'Trainer', 'status' => 'Pending'],
                ['name' => 'Amira Bouazizi', 'email' => 'amira@example.com', 'role' => 'Job Seeker', 'status' => 'Active'],
                ['name' => 'Karim Sfar', 'email' => 'karim@example.com', 'role' => 'Employer', 'status' => 'Inactive'],
            ],
        ]);
    }
}

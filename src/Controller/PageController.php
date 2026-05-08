<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Nav placeholders + public marketing pages.
 */
class PageController extends AbstractController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(Connection $db): JsonResponse
    {
        try {
            $db->executeQuery('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $status = $dbOk ? 'ok' : 'degraded';
        $code = $dbOk ? 200 : 503;

        return new JsonResponse([
            'status' => $status,
            'db' => $dbOk ? 'ok' : 'error',
            'time' => date('c'),
        ], $code);
    }

    #[Route('/offline', name: 'app_offline')]
    public function offline(): Response
    {
        return $this->render('pages/offline.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    #[Route('/pricing', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('pages/pricing.html.twig');
    }

    #[Route('/careers', name: 'app_careers')]
    public function careers(): Response
    {
        return $this->render('pages/careers.html.twig', [
            'positions' => [
                ['title' => 'Full-Stack Developer', 'team' => 'Engineering', 'location' => 'Remote / Tunis', 'type' => 'Full-time'],
                ['title' => 'Senior UI/UX Designer', 'team' => 'Design', 'location' => 'Remote / Tunis', 'type' => 'Full-time'],
                ['title' => 'AI/ML Engineer', 'team' => 'AI R&D', 'location' => 'Remote / Tunis', 'type' => 'Full-time'],
                ['title' => 'Community Manager', 'team' => 'Growth', 'location' => 'Remote / Tunis', 'type' => 'Full-time'],
                ['title' => 'DevOps Engineer', 'team' => 'Infrastructure', 'location' => 'Remote / Tunis', 'type' => 'Full-time'],
            ],
        ]);
    }

    #[Route('/case-studies', name: 'app_case_studies')]
    public function caseStudies(): Response
    {
        return $this->render('pages/case_studies.html.twig', [
            'studies' => [
                [
                    'client' => 'Vermeg',
                    'title' => 'Scaling a Banking Platform with Top Freelancers',
                    'desc' => 'Vermeng used Skilora to onboard 12 senior full-stack developers in under 3 weeks, accelerating their digital banking rollout by 40%.',
                    'tags' => ['Fintech', 'Web Development', 'Scaling'],
                    'results' => ['12 hires', '3 weeks', '40% faster'],
                    'image' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'client' => 'Proxym',
                    'title' => 'Rebuilding a SaaS Product from the Ground Up',
                    'desc' => 'Proxym assembled a cross-functional team of designers and engineers through Skilora to reimagine their analytics dashboard.',
                    'tags' => ['SaaS', 'UI/UX', 'Product Design'],
                    'results' => ['5 freelancers', '2 months', '4.9 rating'],
                    'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'client' => 'Focus Corp',
                    'title' => 'AI-Powered Talent Matching for Enterprise Hiring',
                    'desc' => 'Focus Corp integrated Skilora’s smart-matching API into their internal hiring pipeline, reducing time-to-hire by 60%.',
                    'tags' => ['Enterprise', 'AI', 'API'],
                    'results' => ['60% faster', '100+ roles', '$500K saved'],
                    'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=800&q=80',
                ],
            ],
        ]);
    }
}

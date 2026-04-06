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
            'categories' => [
                ['title' => 'Development & IT', 'icon' => 'code-2', 'count' => '1,846 jobs', 'rating' => '4.9'],
                ['title' => 'Design & Creative', 'icon' => 'palette', 'count' => '987 jobs', 'rating' => '4.8'],
                ['title' => 'Digital Marketing', 'icon' => 'megaphone', 'count' => '654 jobs', 'rating' => '4.7'],
                ['title' => 'Writing & Translation', 'icon' => 'pen-tool', 'count' => '432 jobs', 'rating' => '4.8'],
                ['title' => 'AI & Machine Learning', 'icon' => 'brain', 'count' => '312 jobs', 'rating' => '4.9'],
                ['title' => 'Finance & Accounting', 'icon' => 'calculator', 'count' => '278 jobs', 'rating' => '4.6'],
            ],
            'companies' => [
                ['name' => 'Microsoft', 'logo' => 'https://www.microsoft.com/favicon.ico'],
                ['name' => 'Google', 'logo' => 'https://www.google.com/images/branding/googlelogo/2x/googlelogo_light_color_92x30dp.png'],
                ['name' => 'Amazon', 'logo' => 'https://www.amazon.com/favicon.ico'],
                ['name' => 'Meta', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/4/44/Meta-Logo.png'],
                ['name' => 'Apple', 'logo' => 'https://www.apple.com/favicon.ico'],
                ['name' => 'Netflix', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg'],
                ['name' => 'Spotify', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/19/Spotify_logo_without_text.svg'],
                ['name' => 'Stripe', 'logo' => 'https://stripe.com/img/v3/home/twitter.png'],
            ],
            'talents' => [
                [
                    'name' => 'Amira Bouazizi', 'role' => 'Full-Stack Developer',
                    'image' => 'https://i.pravatar.cc/150?img=47', 'rate' => '$65/hr',
                    'earned' => '$120K+', 'skills' => ['React', 'Node.js', 'TypeScript', 'AWS'],
                ],
                [
                    'name' => 'Youssef Gharbi', 'role' => 'UI/UX Designer',
                    'image' => 'https://i.pravatar.cc/150?img=12', 'rate' => '$55/hr',
                    'earned' => '$85K+', 'skills' => ['Figma', 'Framer', 'Tailwind', 'Motion'],
                ],
                [
                    'name' => 'Fatma Trabelsi', 'role' => 'AI Engineer',
                    'image' => 'https://i.pravatar.cc/150?img=32', 'rate' => '$90/hr',
                    'earned' => '$200K+', 'skills' => ['Python', 'PyTorch', 'LLMs', 'MLOps'],
                ],
                [
                    'name' => 'Karim Sfar', 'role' => 'DevOps Engineer',
                    'image' => 'https://i.pravatar.cc/150?img=59', 'rate' => '$75/hr',
                    'earned' => '$150K+', 'skills' => ['Docker', 'K8s', 'Terraform', 'CI/CD'],
                ],
            ],
            'process' => [
                ['number' => '01', 'title' => 'Post your project', 'desc' => 'Describe what you need. Get matched with experts within minutes.'],
                ['number' => '02', 'title' => 'Review & hire', 'desc' => 'Compare proposals, portfolios, and reviews. Choose the perfect fit.'],
                ['number' => '03', 'title' => 'Get it done', 'desc' => 'Collaborate in real-time. Pay securely when you\'re satisfied.'],
            ],
        ]);
    }
}

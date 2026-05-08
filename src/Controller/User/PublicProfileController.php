<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\PortfolioItemRepository;
use App\Repository\ProfileRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\AppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicProfileController extends AppController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProfileRepository $profileRepository,
        private readonly SkillRepository $skillRepository,
        private readonly PortfolioItemRepository $portfolioItemRepository,
    ) {
    }

    #[Route('/profil/{id}', name: 'app_public_profile', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        $skills = $profile ? $this->skillRepository->findBy(['profile' => $profile], ['yearsExperience' => 'DESC']) : [];
        $portfolioItems = $this->portfolioItemRepository->findBy(['user' => $user], ['isFeatured' => 'DESC', 'createdDate' => 'DESC'], 6);

        return $this->render('user/public_profile.html.twig', [
            'target_user'    => $user,
            'profile'        => $profile,
            'skills'         => $skills,
            'portfolio_items'=> $portfolioItems,
            'reviews'        => [],
            'average_rating' => null,
            'review_count'   => 0,
        ]);
    }
}

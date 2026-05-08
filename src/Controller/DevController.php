<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[When(env: 'dev')]
#[Route('/dev')]
class DevController extends AbstractController
{
    private function guardDev(): void
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException('Not found.');
        }
    }

    #[Route('/login/{username}', name: 'dev_login', methods: ['GET'])]
    public function loginAs(
        string $username,
        UserRepository $userRepository,
        Security $security,
    ): Response {
        $this->guardDev();

        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            throw $this->createNotFoundException("Dev user '$username' not found in DB.");
        }

        $security->login($user, \App\Security\LoginAuthenticator::class);

        // Redirect to the correct dashboard based on role
        $route = match (strtoupper($user->getRole() ?? '')) {
            'ADMIN'   => 'app_dashboard',
            'TRAINER' => 'app_trainer_dashboard',
            default   => 'app_workspace',
        };

        return $this->redirectToRoute($route);
    }

    #[Route('/logout', name: 'dev_logout', methods: ['GET'])]
    public function devLogout(Security $security): Response
    {
        $this->guardDev();

        $security->logout(false);

        return $this->redirectToRoute('app_home');
    }

    #[Route('/spatial-ui', name: 'dev_spatial_ui', methods: ['GET'])]
    public function spatialUi(): Response
    {
        $this->guardDev();
        return $this->render('pages/spatial_test.html.twig');
    }
}

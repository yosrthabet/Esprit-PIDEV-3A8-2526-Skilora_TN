<?php

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authUtils): Response
    {
        // Already logged in? Redirect to their dashboard
        if ($this->getUser()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $route = match (strtoupper($user->getRole() ?? '')) {
                'ADMIN' => 'app_dashboard',
                'EMPLOYER' => 'app_employer_dashboard',
                'TRAINER' => 'app_trainer_dashboard',
                default => 'app_workspace',
            };
            return $this->redirectToRoute($route);
        }

        $error = $authUtils->getLastAuthenticationError();

        return $this->render('auth/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'login_error' => $error ? $error->getMessageKey() : null,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        // Intercepted by the firewall — never actually executes
        throw new \LogicException('This should never be reached.');
    }
}

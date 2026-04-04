<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($user = $this->getUser()) {
            $roles = $user->getRoles();
            if (\in_array('ROLE_EMPLOYER', $roles, true)) {
                return $this->redirectToRoute('app_employer_dashboard');
            }
            if (\in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('app_dashboard');
            }

            return $this->redirectToRoute('app_candidate_offres_index');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank — it is intercepted by the logout key on your firewall.');
    }
}

<?php

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    /**
     * Formulaire de connexion (GET uniquement). Les identifiants sont envoyés en POST vers app_login_check,
     * intercepté par le pare-feu Symfony — ne pas fusionner GET+POST sur la même route.
     */
    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Cible du formulaire ; exécution jamais atteinte si le pare-feu traite le POST correctement.
     */
    #[Route('/login_check', name: 'app_login_check', methods: ['POST'])]
    public function loginCheck(): never
    {
        throw new \LogicException('login_check doit être géré par le pare-feu security (form_login).');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
    }
}

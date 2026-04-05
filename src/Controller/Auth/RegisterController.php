<?php

namespace App\Controller\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            // Validate CSRF token
            $token = $request->request->get('_csrf_token');
            if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('register', $token))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('app_register');
            }

            $username = $request->request->get('username');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $fullName = $request->request->get('full_name');

            // Validate input
            if (!$username || !$password || !$fullName) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->redirectToRoute('app_register');
            }

            // Create new user
            $user = new User();
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setFullName($fullName);
            $user->setRole('USER');
            $user->setIsActive(true);

            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Registration successful! You can now login.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Username or email already exists!');
            }
        }

        return $this->render('auth/register.html.twig');
    }
}

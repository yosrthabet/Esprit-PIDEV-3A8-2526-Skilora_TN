<?php

namespace App\Controller\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginAuthenticator;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginAuthenticator $authenticator,
    ): Response {
        // Already logged in? Redirect
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', $request->request->getString('_csrf_token'))) {
                return $this->render('auth/register.html.twig', [
                    'errors' => ['Invalid form submission. Please try again.'],
                ]);
            }

            $username = trim($request->request->getString('username'));
            $email = trim($request->request->getString('email'));
            $fullName = trim($request->request->getString('full_name'));
            $password = $request->request->getString('password');
            $confirmPassword = $request->request->getString('confirm_password');
            $role = $request->request->getString('role', 'USER');

            // Validate
            $errors = [];

            if (empty($username) || strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters.';
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if (empty($fullName)) {
                $errors[] = 'Full name is required.';
            }

            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            // Validate role — only allow USER, EMPLOYER, TRAINER
            $allowedRoles = ['USER', 'EMPLOYER', 'TRAINER'];
            $role = strtoupper($role);
            if (!in_array($role, $allowedRoles, true)) {
                $role = 'USER';
            }

            // Check uniqueness
            if (empty($errors)) {
                $existing = $em->getRepository(User::class)->findOneBy(['username' => $username]);
                if ($existing) {
                    $errors[] = 'Username is already taken.';
                }

                $existingEmail = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingEmail) {
                    $errors[] = 'Email is already registered.';
                }
            }

            if (!empty($errors)) {
                return $this->render('auth/register.html.twig', [
                    'errors' => $errors,
                    'last_username' => $username,
                    'last_email' => $email,
                    'last_full_name' => $fullName,
                    'last_role' => $role,
                ]);
            }

            // Create user
            $user = new User();
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setFullName($fullName);
            $user->setRole($role);
            $user->setActive(true);
            $user->setVerified(false);
            $user->setPassword($passwordHasher->hashPassword($user, $password));

            $em->persist($user);
            $em->flush();

            // Auto-login after registration
            return $userAuthenticator->authenticateUser($user, $authenticator, $request);
        }

        return $this->render('auth/register.html.twig');
    }
}

<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class OAuthRegisterController extends AbstractController
{
    #[Route('/oauth/complete-registration', name: 'app_oauth_complete_registration')]
    public function completeRegistration(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginAuthenticator $authenticator,
    ): Response {
        $session = $request->getSession();
        $oauthData = $session->get('oauth_registration');

        if (!$oauthData) {
            return $this->redirectToRoute('app_register');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('oauth_register', $request->request->getString('_csrf_token'))) {
                $errors[] = 'Invalid form submission. Please try again.';
            }

            $username = trim($request->request->getString('username'));
            $email = trim($request->request->getString('email'));
            $fullName = trim($request->request->getString('full_name'));
            $password = $request->request->getString('password');
            $confirmPassword = $request->request->getString('confirm_password');
            $role = strtoupper($request->request->getString('role', 'USER'));

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

            $allowedRoles = ['USER', 'EMPLOYER', 'TRAINER'];
            if (!in_array($role, $allowedRoles, true)) {
                $role = 'USER';
            }

            if (empty($errors)) {
                if ($em->getRepository(User::class)->findOneBy(['username' => $username])) {
                    $errors[] = 'Username is already taken.';
                }
                if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                    $errors[] = 'Email is already registered.';
                }
            }

            if (empty($errors)) {
                $user = new User();
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setFullName($fullName);
                $user->setRole($role);
                $user->setActive(true);
                $user->setVerified(true);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setPhotoUrl($oauthData['avatar_url'] ?? null);

                if ($oauthData['provider'] === 'github') {
                    $user->setGithubId($oauthData['github_id']);
                }

                $em->persist($user);
                $em->flush();

                $session->remove('oauth_registration');

                return $userAuthenticator->authenticateUser($user, $authenticator, $request);
            }
        }

        return $this->render('auth/oauth_complete.html.twig', [
            'oauth_data' => $oauthData,
            'errors' => $errors,
            'last_username' => $username ?? ($oauthData['nickname'] ?? ''),
            'last_email' => $email ?? ($oauthData['email'] ?? ''),
            'last_full_name' => $fullName ?? ($oauthData['full_name'] ?? ''),
            'last_role' => $role ?? 'USER',
        ]);
    }
}

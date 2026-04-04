<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): Response {
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

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $email = trim((string) $request->request->get('email', ''));
            $plainPassword = (string) $request->request->get('password', '');
            $confirm = (string) $request->request->get('password_confirm', '');

            if ($plainPassword === '' || $plainPassword !== $confirm) {
                $this->addFlash('error', 'Les mots de passe doivent correspondre et ne pas être vides.');

                return $this->render('security/register.html.twig', ['email' => $email]);
            }

            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing) {
                $this->addFlash('error', 'Un compte existe déjà avec cette adresse e-mail.');

                return $this->render('security/register.html.twig', ['email' => $email]);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setUsername($this->makeUniqueUsername($email, $userRepository));
            $user->setFullName($this->defaultFullNameFromEmail($email));
            $user->setRole('USER');
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', ['email' => '']);
    }

    private function makeUniqueUsername(string $email, UserRepository $userRepository): string
    {
        $local = strstr($email, '@', true) ?: 'user';
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', $local);
        $base = substr($base !== '' ? $base : 'user', 0, 50);
        $username = $base;
        $n = 0;
        while ($userRepository->findOneBy(['username' => $username])) {
            $suffix = '_' . substr(bin2hex(random_bytes(3)), 0, 6);
            $username = substr(substr($base, 0, 50 - strlen($suffix)) . $suffix, 0, 50);
            if (++$n > 25) {
                $username = substr(bin2hex(random_bytes(16)), 0, 50);
            }
        }

        return $username;
    }

    private function defaultFullNameFromEmail(string $email): string
    {
        $local = strstr($email, '@', true) ?: $email;

        return ucfirst(str_replace(['.', '_', '-'], ' ', $local));
    }
}

<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/settings/password', name: 'app_settings_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('settings_password', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        $currentPassword = $request->request->get('current_password', '');
        $newPassword = $request->request->get('new_password', '');
        $confirmPassword = $request->request->get('confirm_password', '');

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('app_settings');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New passwords do not match.');
            return $this->redirectToRoute('app_settings');
        }

        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'New password must be at least 6 characters.');
            return $this->redirectToRoute('app_settings');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        $this->addFlash('success', 'Password updated successfully');
        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings/profile', name: 'app_settings_profile', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('settings_profile', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        $email = trim($request->request->get('email', ''));
        $fullName = trim($request->request->get('full_name', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Please enter a valid email address.');
            return $this->redirectToRoute('app_settings');
        }

        $existing = $userRepository->findOneBy(['email' => $email]);
        if ($existing && $existing->getId() !== $user->getId()) {
            $this->addFlash('error', 'This email is already in use by another account.');
            return $this->redirectToRoute('app_settings');
        }

        $user->setEmail($email);
        $user->setFullName($fullName ?: null);
        $em->flush();

        $this->addFlash('success', 'Profile updated successfully.');
        return $this->redirectToRoute('app_settings');
    }
}

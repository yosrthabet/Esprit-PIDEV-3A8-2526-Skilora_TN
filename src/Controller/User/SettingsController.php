<?php

namespace App\Controller\User;

use App\Entity\LoginHistory;
use App\Entity\UserSession;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use App\Controller\AppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SettingsController extends AppController
{
    #[Route('/settings', name: 'app_settings', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        TotpAuthenticatorInterface $totpAuthenticator,
    ): Response {
        $user = $this->getAppUser();

        // Login history (last 10)
        $loginHistory = $em->getRepository(LoginHistory::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            10
        );

        // Active sessions
        $sessions = $em->getRepository(UserSession::class)->findBy(
            ['user' => $user],
            ['lastActivity' => 'DESC']
        );

        // 2FA QR content (if setting up)
        $totpQrContent = null;
        $totpQrDataUri = null;
        if ($user->hasTotpSecret() && !$user->isTotpAuthenticationEnabled()) {
            $totpQrContent = $totpAuthenticator->getQRContent($user);
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($totpQrContent)
                ->encoding(new Encoding('UTF-8'))
                ->size(200)
                ->margin(10)
                ->build();
            $totpQrDataUri = $result->getDataUri();
        }

        return $this->render('user/settings/index.html.twig', [
            'user' => $user,
            'tab' => $request->query->get('tab', 'account'),
            'login_history' => $loginHistory,
            'sessions' => $sessions,
            'totp_qr_content' => $totpQrContent,
            'totp_qr_data_uri' => $totpQrDataUri,
            'current_session_id' => $request->getSession()->getId(),
        ]);
    }

    #[Route('/settings/password', name: 'app_settings_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('settings_password', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $currentPassword = $request->request->getString('current_password');
        $newPassword = $request->request->getString('new_password');
        $confirmPassword = $request->request->getString('confirm_password');

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
        if (!$this->isCsrfTokenValid('settings_profile', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getAppUser();
        $email = trim($request->request->getString('email'));
        $fullName = trim($request->request->getString('full_name'));

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

    #[Route('/settings/delete-account', name: 'app_settings_delete_account', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $em,
        ProfileRepository $profileRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_account', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getAppUser();
        $userId = $user->getId();

        $profile = $profileRepository->findOneBy(['user' => $user]);
        if ($profile !== null) {
            $profile->setFirstName(null);
            $profile->setLastName(null);
            $profile->setPhone(null);
            $profile->setBio(null);
            $profile->setPhotoUrl(null);
            $profile->setCvUrl(null);
        }

        $user->setFullName(null);
        $user->setEmail('deleted_' . $userId . '@skilora.dev');
        $user->setActive(false);
        $user->setPhotoUrl(null);
        $em->flush();

        return $this->redirectToRoute('app_logout');
    }
}

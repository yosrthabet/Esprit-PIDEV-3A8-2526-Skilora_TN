<?php

namespace App\Controller\User;

use App\Entity\LoginHistory;
use App\Entity\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/settings')]
class SecuritySettingsController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    // ── 2FA Setup ──────────────────────────────────────────────

    #[Route('/2fa/enable', name: 'app_settings_2fa_enable', methods: ['POST'])]
    public function enable2fa(
        Request $request,
        TotpAuthenticatorInterface $totpAuthenticator,
    ): Response {
        if (!$this->isCsrfTokenValid('2fa_enable', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        if (!$user->getTotpSecret()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());
        }

        // Store secret temporarily — user must verify before it's activated
        $request->getSession()->set('2fa_setup_secret', $user->getTotpSecret());
        $this->em->flush();

        return $this->redirectToRoute('app_settings', ['tab' => 'security', '_fragment' => '2fa-verify']);
    }

    #[Route('/2fa/verify', name: 'app_settings_2fa_verify', methods: ['POST'])]
    public function verify2fa(
        Request $request,
        TotpAuthenticatorInterface $totpAuthenticator,
    ): Response {
        if (!$this->isCsrfTokenValid('2fa_verify', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $code = $request->request->get('code', '');

        if ($totpAuthenticator->checkCode($user, $code)) {
            $user->setTwoFactorEnabled(true);
            $user->setTwoFactorEnabledAt(new \DateTimeImmutable());
            $this->em->flush();
            $request->getSession()->remove('2fa_setup_secret');
            $this->addFlash('success', 'Two-factor authentication has been enabled.');
        } else {
            $this->addFlash('error', 'Invalid verification code. Please try again.');
        }

        return $this->redirectToRoute('app_settings', ['tab' => 'security']);
    }

    #[Route('/2fa/disable', name: 'app_settings_2fa_disable', methods: ['POST'])]
    public function disable2fa(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('2fa_disable', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $user->setTwoFactorEnabled(false);
        $user->setTotpSecret(null);
        $user->setTwoFactorEnabledAt(null);
        $this->em->flush();

        $this->addFlash('success', 'Two-factor authentication has been disabled.');
        return $this->redirectToRoute('app_settings', ['tab' => 'security']);
    }

    // ── Session Manager ────────────────────────────────────────

    #[Route('/sessions/revoke-all', name: 'app_settings_sessions_revoke', methods: ['POST'])]
    public function revokeAllSessions(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('revoke_sessions', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $currentSessionId = $request->getSession()->getId();

        // Delete all sessions except the current one
        $sessions = $this->em->getRepository(UserSession::class)->findBy(['user' => $user]);
        foreach ($sessions as $session) {
            if ($session->getSessionId() !== $currentSessionId) {
                $this->em->remove($session);
            }
        }
        $this->em->flush();

        $this->addFlash('success', 'All other sessions have been revoked.');
        return $this->redirectToRoute('app_settings', ['tab' => 'security']);
    }

    #[Route('/sessions/{id}/revoke', name: 'app_settings_session_revoke_one', methods: ['POST'])]
    public function revokeOneSession(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('revoke_session_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $session = $this->em->getRepository(UserSession::class)->find($id);

        if ($session && $session->getUser() === $this->getUser()) {
            $this->em->remove($session);
            $this->em->flush();
            $this->addFlash('success', 'Session revoked.');
        }

        return $this->redirectToRoute('app_settings', ['tab' => 'security']);
    }

    // ── Connected Accounts ─────────────────────────────────────

    #[Route('/oauth/{provider}/unlink', name: 'app_settings_oauth_unlink', methods: ['POST'])]
    public function unlinkOAuth(string $provider, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('unlink_' . $provider, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        // Must keep at least one login method (password or another provider)
        $hasPassword = $user->getPassword() !== null;
        $hasGoogle = $user->getGoogleId() !== null;
        $hasGithub = $user->getGithubId() !== null;
        $loginMethods = (int) $hasPassword + (int) $hasGoogle + (int) $hasGithub;

        if ($loginMethods <= 1) {
            $this->addFlash('error', 'Cannot unlink — you need at least one login method.');
            return $this->redirectToRoute('app_settings', ['tab' => 'security']);
        }

        match ($provider) {
            'google' => $user->setGoogleId(null),
            'github' => $user->setGithubId(null),
            default => throw $this->createNotFoundException(),
        };

        $this->em->flush();
        $this->addFlash('success', ucfirst($provider) . ' account unlinked.');
        return $this->redirectToRoute('app_settings', ['tab' => 'security']);
    }
}

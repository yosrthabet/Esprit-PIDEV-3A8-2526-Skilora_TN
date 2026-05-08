<?php

namespace App\Controller\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class ForgotPasswordController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.mailer_from%')] private string $mailerFrom,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(Request $request, EntityManagerInterface $em, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $submitted = false;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password', $request->request->getString('_token'))) {
                return $this->render('auth/forgot-password.html.twig', [
                    'submitted' => false,
                    'errors' => ['Invalid form submission. Please try again.'],
                ]);
            }

            $email = trim($request->request->getString('email'));
            $submitted = true;

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $user->setResetToken($token);
                    $user->setResetTokenExpiry(new \DateTimeImmutable('+1 hour'));
                    $em->flush();

                    $resetUrl = $urlGenerator->generate('app_reset_password', [
                        'token' => $token,
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    try {
                        $mailer->send((new Email())
                            ->from($this->mailerFrom)
                            ->to(new Address((string) $user->getEmail()))
                            ->subject('Reset your Skilora password')
                            ->text("Open this link to reset your password: {$resetUrl}")
                            ->html(
                                '<div style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px">'
                                . '<h2 style="margin:0 0 16px">Reset your password</h2>'
                                . '<p>You requested a password reset for your Skilora account.</p>'
                                . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES) . '" '
                                . 'style="display:inline-block;padding:12px 24px;background:#18181b;color:#fff;'
                                . 'text-decoration:none;border-radius:6px;font-weight:600">Reset Password</a></p>'
                                . '<p style="color:#71717a;font-size:13px">This link expires in 1 hour. '
                                . 'If you didn\'t request this, ignore this email.</p>'
                                . '<hr style="border:none;border-top:1px solid #e4e4e7;margin:24px 0">'
                                . '<p style="color:#a1a1aa;font-size:12px">Skilora — Tunisia\'s Talent Ecosystem</p>'
                                . '</div>'
                            )
                        );
                    } catch (\Throwable $e) {
                        $this->logger->error('Failed to send password reset email.', [
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $this->render('auth/forgot-password.html.twig', [
            'submitted' => $submitted,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'This password reset link is invalid or has expired.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password_' . $token, $request->request->getString('_token'))) {
                return $this->render('auth/reset-password.html.twig', [
                    'token' => $token,
                    'errors' => ['Invalid form submission. Please try again.'],
                ]);
            }

            $password = $request->request->getString('password');
            $confirmPassword = $request->request->getString('confirm_password');

            if ($password === '' || strlen($password) < 8) {
                return $this->render('auth/reset-password.html.twig', [
                    'token' => $token,
                    'errors' => ['Password must be at least 8 characters.'],
                ]);
            }

            if ($password !== $confirmPassword) {
                return $this->render('auth/reset-password.html.twig', [
                    'token' => $token,
                    'errors' => ['Passwords do not match.'],
                ]);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setResetToken(null);
            $user->expireResetToken();
            $em->flush();

            $this->addFlash('success', 'Your password has been reset. You can now sign in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset-password.html.twig', [
            'token' => $token,
        ]);
    }
}

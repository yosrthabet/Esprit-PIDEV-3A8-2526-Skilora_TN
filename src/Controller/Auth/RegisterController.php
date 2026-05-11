<?php

namespace App\Controller\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegisterController extends AbstractController
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
        #[Autowire('%app.mailer_from%')] private readonly string $mailerFrom,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
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

            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            $allowedRoles = ['USER', 'EMPLOYER', 'TRAINER'];
            $role = strtoupper($role);
            if (!in_array($role, $allowedRoles, true)) {
                $role = 'USER';
            }

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

            $mailerDsn = $_ENV['MAILER_DSN'] ?? '';
            $canSendEmail = !str_starts_with($mailerDsn, 'null://');

            if ($canSendEmail) {
                try {
                    $this->sendVerificationEmail($user);
                    $this->sendWelcomeEmail($user);
                } catch (\Throwable) {
                    $canSendEmail = false;
                }
            }

            if (!$canSendEmail) {
                $user->setVerified(true);
                $em->flush();
                $this->addFlash('success', 'Account created! You can now log in.');
            } else {
                $this->addFlash('success', 'Account created! Please check your email to verify your address before logging in.');
            }
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->query->get('id');
        if (!$userId) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(User::class)->find((int) $userId);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified. Please log in.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $email = $user->getEmail();
            if ($email === null) {
                $this->addFlash('error', 'Invalid verification link.');
                return $this->redirectToRoute('app_login');
            }

            $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), $email);
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());
            return $this->redirectToRoute('app_login');
        }

        $user->setVerified(true);
        $em->flush();

        $this->addFlash('success', 'Your email has been verified! You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify/resend', name: 'app_verify_email_resend', methods: ['POST'])]
    public function resendVerification(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('resend_verification', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid request.');
            return $this->redirectToRoute('app_login');
        }

        $email = trim($request->request->getString('email'));
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user && !$user->isVerified()) {
            try {
                $this->sendVerificationEmail($user);
            } catch (\Throwable) {
                // SMTP may be blocked; silently continue
            }
        }

        // Always show the same message to avoid user enumeration
        $this->addFlash('success', 'If that email is registered and unverified, we sent a new verification link.');
        return $this->redirectToRoute('app_login');
    }

    private function sendWelcomeEmail(User $user): void
    {
        try {
            $this->mailer->send(
                (new TemplatedEmail())
                    ->from(new Address($this->mailerFrom, 'Skilora'))
                    ->to(new Address((string) $user->getEmail(), $user->getDisplayName()))
                    ->subject('Welcome to Skilora!')
                    ->htmlTemplate('emails/welcome.html.twig')
                    ->context([
                        'user' => $user,
                        'dashboard_url' => $this->generateUrl('app_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
                    ])
            );
        } catch (\Throwable) {
            // Welcome email failure is non-critical
        }
    }

    private function sendVerificationEmail(User $user): void
    {
        $email = $user->getEmail();
        if ($email === null) {
            return;
        }

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            $email,
            ['id' => $user->getId()],
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from(new Address($this->mailerFrom, 'Skilora'))
                ->to(new Address($email, $user->getDisplayName()))
                ->subject('Verify your Skilora account')
                ->htmlTemplate('emails/verify_email.html.twig')
                ->context([
                    'signedUrl' => $signatureComponents->getSignedUrl(),
                    'expiresAt' => $signatureComponents->getExpiresAt(),
                    'user' => $user,
                ])
        );
    }
}

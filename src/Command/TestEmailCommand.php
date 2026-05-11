<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(name: 'app:test-email', description: 'Send test emails to verify SMTP and templates')]
class TestEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'Recipient email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = $input->getArgument('to');
        $from = $_ENV['MAILER_FROM'] ?? 'hello@skilora.dev';

        $io->title('Skilora SMTP Test');
        $io->text("From: $from");
        $io->text("To:   $to");
        $io->newLine();

        // 1. Welcome email
        $io->section('Sending Welcome email...');
        try {
            $welcomeHtml = $this->twig->render('emails/welcome.html.twig', [
                'user' => (object) ['fullName' => 'Test User', 'username' => 'testuser'],
                'dashboard_url' => 'https://skilora.dev/dashboard',
            ]);
            $email = (new Email())
                ->from($from)
                ->to($to)
                ->subject('[Skilora Test] Welcome Email')
                ->html($welcomeHtml);
            $this->mailer->send($email);
            $io->success('Welcome email sent!');
        } catch (\Throwable $e) {
            $io->error('Welcome email failed: ' . $e->getMessage());
        }

        // 2. Verify email
        $io->section('Sending Verify Email...');
        try {
            $verifyHtml = $this->twig->render('emails/verify_email.html.twig', [
                'user' => (object) ['fullName' => 'Test User', 'username' => 'testuser'],
                'signedUrl' => 'https://skilora.dev/verify?token=test-token-123',
                'expiresAt' => new \DateTimeImmutable('+1 hour'),
            ]);
            $email = (new Email())
                ->from($from)
                ->to($to)
                ->subject('[Skilora Test] Verify Your Email')
                ->html($verifyHtml);
            $this->mailer->send($email);
            $io->success('Verify email sent!');
        } catch (\Throwable $e) {
            $io->error('Verify email failed: ' . $e->getMessage());
        }

        // 3. Support notification (plain)
        $io->section('Sending Support Notification email...');
        try {
            $html = <<<HTML
            <div style="font-family:-apple-system,sans-serif;max-width:560px;margin:40px auto;background:#18181b;border:1px solid #27272a;border-radius:12px;padding:40px;color:#d4d4d8;">
                <div style="font-size:22px;font-weight:700;color:#fff;margin-bottom:32px;">Skilora</div>
                <h1 style="font-size:20px;font-weight:600;color:#fff;margin:0 0 12px;">Support Ticket Update</h1>
                <p style="color:#a1a1aa;font-size:14px;line-height:1.6;">Hello Test User,</p>
                <p style="color:#a1a1aa;font-size:14px;line-height:1.6;">Your support ticket <strong style="color:#fff;">#42 — Login Issue</strong> status has been changed from <strong style="color:#f59e0b;">Open</strong> to <strong style="color:#22c55e;">Resolved</strong>.</p>
                <p style="color:#a1a1aa;font-size:14px;line-height:1.6;">You can view your ticket in the support center.</p>
                <p style="color:#52525b;font-size:12px;margin-top:32px;text-align:center;">— Skilora Support Team</p>
            </div>
            HTML;
            $email = (new Email())
                ->from($from)
                ->to($to)
                ->subject('[Skilora Test] Support Ticket Update')
                ->html($html);
            $this->mailer->send($email);
            $io->success('Support notification email sent!');
        } catch (\Throwable $e) {
            $io->error('Support email failed: ' . $e->getMessage());
        }

        $io->newLine();
        $io->info("Check inbox at: $to");

        return Command::SUCCESS;
    }
}

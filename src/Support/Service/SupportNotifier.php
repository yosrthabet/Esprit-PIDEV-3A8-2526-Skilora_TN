<?php

declare(strict_types=1);

namespace App\Support\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Support\Entity\SupportTicket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SupportNotifier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom = 'hello@skilora.dev',
    ) {
    }

    public function notifyAdminsNewTicket(SupportTicket $ticket): void
    {
        foreach ($this->userRepository->findBy(['role' => 'ADMIN']) as $admin) {
            $this->notify($admin, 'New support ticket', sprintf('#%d · %s', $ticket->getId(), $ticket->getSubject()), $ticket);
        }
        $this->entityManager->flush();
    }

    public function notifyRequester(SupportTicket $ticket, string $title, string $message): void
    {
        $this->notify($ticket->getRequester(), $title, $message, $ticket);
        $this->entityManager->flush();
    }

    public function sendStatusChangeEmail(SupportTicket $ticket, string $oldStatus, string $newStatus): void
    {
        $user = $ticket->getRequester();
        $emailAddress = $user->getEmail();
        if ($emailAddress === null) {
            return;
        }
        try {
            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($emailAddress)
                ->subject(sprintf('[Skilora Support] Ticket #%d — Status updated', $ticket->getId()))
                ->html(sprintf(
                    '<p>Hello %s,</p><p>Your support ticket <strong>#%d — %s</strong> status has been changed from <strong>%s</strong> to <strong>%s</strong>.</p><p>You can view your ticket in the support center.</p><p>— Skilora Support Team</p>',
                    htmlspecialchars($user->getDisplayName()),
                    $ticket->getId(),
                    htmlspecialchars($ticket->getSubject()),
                    htmlspecialchars($oldStatus),
                    htmlspecialchars($newStatus),
                ));

            $this->mailer->send($email);
        } catch (\Throwable) {
        }
    }

    private function notify(User $user, string $title, string $message, SupportTicket $ticket): void
    {
        $this->entityManager->persist((new Notification())
            ->setUser($user)
            ->setType('support')
            ->setTitle($title)
            ->setMessage($message)
            ->setIcon('🎫')
            ->setReferenceType('support_ticket')
            ->setReferenceId($ticket->getId()));
    }
}

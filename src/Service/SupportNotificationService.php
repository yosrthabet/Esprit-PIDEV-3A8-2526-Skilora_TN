<?php

namespace App\Service;

use App\Entity\Ticket;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class SupportNotificationService
{
    private string $senderEmail = 'raedbcc5@gmail.com';
    private string $receiverEmail = 'raed.Bouchaddakh@esprit.tn';

    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {
    }

    public function notifyStatusChange(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($this->receiverEmail)
            ->subject('Ticket Status Updated: #' . $ticket->getId())
            ->html($this->twig->render('support/emails/status_change.html.twig', [
                'ticket' => $ticket,
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
            ]));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error in production
        }
    }
}

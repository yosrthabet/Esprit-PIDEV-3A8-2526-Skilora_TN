<?php

declare(strict_types=1);

namespace App\Support\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Support\Entity\SupportTicket;
use Doctrine\ORM\EntityManagerInterface;

class SupportNotifier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
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

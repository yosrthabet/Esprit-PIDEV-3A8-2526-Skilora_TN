<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Finance\Entity\Contract;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class FinanceNotifier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function notifyParticipant(User $user, Contract $contract, string $type, string $title, string $message): void
    {
        $this->notify($user, $contract, $type, $title, $message);
    }

    public function notifyAdmins(Contract $contract, string $title, string $message): void
    {
        foreach ($this->userRepository->findBy(['role' => 'ADMIN']) as $admin) {
            $this->notify($admin, $contract, 'finance.dispute', $title, $message);
        }
    }

    private function notify(User $user, Contract $contract, string $type, string $title, string $message): void
    {
        if ($contract->getId() === null) {
            return;
        }
        $this->entityManager->persist((new Notification())
            ->setUser($user)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setIcon('money')
            ->setReferenceType('contract')
            ->setReferenceId($contract->getId()));
    }
}

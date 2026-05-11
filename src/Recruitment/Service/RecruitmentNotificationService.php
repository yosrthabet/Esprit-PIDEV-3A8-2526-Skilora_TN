<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class RecruitmentNotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function notifyHireOfferReceived(User $candidate, string $jobTitle, string $companyName): void
    {
        $this->create(
            $candidate,
            'hire_offer',
            'Offre d\'embauche reçue',
            sprintf('Vous avez reçu une offre pour "%s" de %s.', $jobTitle, $companyName),
            'briefcase',
            'hire_offer',
        );
    }

    public function notifyApplicationAccepted(User $candidate, string $jobTitle): void
    {
        $this->create(
            $candidate,
            'application_accepted',
            'Candidature acceptée',
            sprintf('Votre candidature pour "%s" a été acceptée.', $jobTitle),
            'check-circle',
            'application',
        );
    }

    public function notifyApplicationRejected(User $candidate, string $jobTitle): void
    {
        $this->create(
            $candidate,
            'application_rejected',
            'Candidature refusée',
            sprintf('Votre candidature pour "%s" a été refusée.', $jobTitle),
            'x-circle',
            'application',
        );
    }

    public function notifyInterviewScheduled(User $candidate, string $jobTitle, \DateTimeInterface $scheduledAt): void
    {
        $this->create(
            $candidate,
            'interview_scheduled',
            'Entretien programmé',
            sprintf('Un entretien pour "%s" est prévu le %s.', $jobTitle, $scheduledAt->format('d/m/Y à H:i')),
            'calendar',
            'interview',
        );
    }

    private function create(User $user, string $type, string $title, string $message, string $icon, string $refType, ?int $refId = null): void
    {
        $notification = (new Notification())
            ->setUser($user)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setIcon($icon)
            ->setReferenceType($refType);

        if ($refId !== null) {
            $notification->setReferenceId($refId);
        }

        $this->em->persist($notification);
        $this->em->flush();
    }
}

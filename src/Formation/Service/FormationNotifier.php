<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Formation\Entity\Certificate;
use App\Formation\Entity\Enrollment;
use App\Formation\Entity\Formation;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class FormationNotifier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function notifyAdminsNewFormation(Formation $formation): void
    {
        foreach ($this->userRepository->findBy(['role' => 'ADMIN']) as $admin) {
            $this->notify($admin, 'formation.created', 'New formation pending review', $formation->getTitle(), 'formation', $formation->getId());
        }
        $this->entityManager->flush();
    }

    public function notifyTrainerPublished(Formation $formation): void
    {
        $this->notify($formation->getTrainer(), 'formation.published', 'Formation published', $formation->getTitle(), 'formation', $formation->getId());
        $this->entityManager->flush();
    }

    public function notifyTrainerEnrollment(Enrollment $enrollment): void
    {
        $formation = $enrollment->getFormation();
        $this->notify(
            $formation->getTrainer(),
            'formation.enrolled',
            'New student enrolled',
            $enrollment->getUser()->getDisplayName() . ' joined ' . $formation->getTitle(),
            'formation',
            $formation->getId(),
        );
        $this->entityManager->flush();
    }

    public function notifyTrainerFormationPublished(Formation $formation): void
    {
        $this->notify(
            $formation->getTrainer(),
            'formation.ai_published',
            'Formation approved & published',
            'Your formation "' . $formation->getTitle() . '" passed AI review (score: ' . ($formation->getReviewScore() ?? '?') . ') and is now live!',
            'formation',
            $formation->getId(),
        );
        $this->entityManager->flush();
    }

    public function notifyTrainerFormationRefused(Formation $formation): void
    {
        $this->notify(
            $formation->getTrainer(),
            'formation.ai_refused',
            'Formation review failed',
            'Your formation "' . $formation->getTitle() . '" did not pass AI review. Reason: ' . ($formation->getReviewNote() ?? 'quality below threshold'),
            'formation',
            $formation->getId(),
        );
        $this->entityManager->flush();
    }

    public function notifyCertificateIssued(Certificate $certificate): void
    {
        $this->notify(
            $certificate->getEnrollment()->getUser(),
            'certificate.issued',
            'Certificate issued',
            $certificate->getEnrollment()->getFormation()->getTitle(),
            'certificate',
            $certificate->getId(),
        );
        $this->entityManager->flush();
    }

    private function notify(User $user, string $type, string $title, string $message, string $referenceType, ?int $referenceId): void
    {
        if ($referenceId === null) {
            return;
        }

        $this->entityManager->persist((new Notification())
            ->setUser($user)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setIcon('book')
            ->setReferenceType($referenceType)
            ->setReferenceId($referenceId));
    }
}

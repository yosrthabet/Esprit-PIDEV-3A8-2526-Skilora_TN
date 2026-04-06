<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Repository\ApplicationsTableGateway;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Candidatures employeur : lecture / mise à jour exclusivement via la table SQL {@code applications}.
 */
final class EmployerApplicationService
{
    public function __construct(
        private readonly ApplicationsTableGateway $applicationsTableGateway,
    ) {
    }

    public function assertEmployerManagesApplication(User $employer, int $applicationId): void
    {
        $uid = $employer->getId();
        if ($uid === null) {
            throw new AccessDeniedHttpException('Accès refusé.');
        }

        if (!$this->applicationsTableGateway->employerOwnsApplication($applicationId, (int) $uid)) {
            throw new AccessDeniedHttpException(
                'Candidature introuvable ou vous n’avez pas le droit d’y accéder.',
            );
        }
    }

    public function updateStatus(User $employer, int $applicationId, string $newStatus): void
    {
        $this->assertEmployerManagesApplication($employer, $applicationId);

        if (!ApplicationStatus::isValidEmployerStatus($newStatus)) {
            throw new \InvalidArgumentException('Statut de candidature invalide.');
        }

        $this->applicationsTableGateway->updateStatus($applicationId, $newStatus);
    }
}

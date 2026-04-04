<?php

declare(strict_types=1);

namespace App\Recruitment\ViewModel;

/**
 * Ligne liste « Candidatures » employeur (données métier, pas le schéma SQL brut).
 */
final readonly class EmployerCandidatureListItem
{
    public function __construct(
        public int $id,
        public int $candidateUserId,
        public string $candidateName,
        public ?string $candidateEmail,
        public int $jobOfferId,
        public string $jobTitle,
        public \DateTimeImmutable $appliedAt,
        /** Valeur stockée (IN_PROGRESS, ACCEPTED, REJECTED, …) — pour actions statut. */
        public string $statusRaw,
        public string $statusLabelFr,
    ) {
    }
}

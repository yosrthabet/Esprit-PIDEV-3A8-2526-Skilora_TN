<?php

declare(strict_types=1);

namespace App\Recruitment\ViewModel;

/**
 * Fiche détail candidature côté employeur (profil, CV, lettre).
 */
final readonly class EmployerCandidatureProfileView
{
    public function __construct(
        public int $id,
        public string $candidateName,
        public ?string $candidateEmail,
        public int $jobOfferId,
        public string $jobTitle,
        public ?string $jobWorkType,
        public \DateTimeImmutable $appliedAt,
        public string $statusRaw,
        public string $statusLabelFr,
        public ?string $coverLetter,
    ) {
    }
}

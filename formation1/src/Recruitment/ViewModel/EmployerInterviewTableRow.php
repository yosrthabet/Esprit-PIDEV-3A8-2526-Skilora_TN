<?php

declare(strict_types=1);

namespace App\Recruitment\ViewModel;

use App\Recruitment\InterviewFormat;

/**
 * Candidature acceptée + éventuelle ligne {@code interviews} (LEFT JOIN).
 * Les champs d’entretien (date, lieu…) ne sont renseignés qu’après insertion dans {@code interviews}.
 */
final readonly class EmployerInterviewTableRow
{
    public function __construct(
        public ?int $interviewId,
        public int $applicationId,
        public ?\DateTimeImmutable $scheduledAt,
        public string $format,
        public ?string $location,
        public string $lifecycleStatus,
        public \DateTimeImmutable $appliedAt,
        public string $applicationStatus,
        public int $jobOfferId,
        public string $jobTitle,
        public string $candidateName,
        public ?string $candidateEmail,
    ) {
    }

    public function hasInterviewRow(): bool
    {
        return $this->interviewId !== null && $this->scheduledAt !== null;
    }

    public function getFormatLabelFr(): string
    {
        return InterviewFormat::labelFr($this->format);
    }
}

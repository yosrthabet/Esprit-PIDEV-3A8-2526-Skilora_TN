<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Enum\InterviewFormat;
use App\Recruitment\Entity\JobInterview;

final class InterviewWhatsAppMessageFactory
{
    public function build(
        string $candidateName,
        string $jobTitle,
        JobInterview $interview,
        ?string $note,
    ): string {
        $scheduledAt = $interview->getScheduledAt();

        $date = $scheduledAt->format('d/m/Y');
        $time = $scheduledAt->format('H:i');
        $format = $interview->getFormat();
        $type = $format === InterviewFormat::ONSITE ? 'Présentiel' : ($format === InterviewFormat::PHONE ? 'Téléphone' : 'Online');

        $lines = [];
        $lines[] = "Bonjour {$candidateName},";
        $lines[] = '';
        $lines[] = "Votre candidature pour le poste *{$jobTitle}* a été retenue.";
        $lines[] = '';
        $lines[] = "Votre entretien a été planifié :";
        $lines[] = "Date : {$date}";
        $lines[] = "Heure : {$time}";
        $lines[] = "Type : {$type}";

        if ($format === InterviewFormat::ONSITE) {
            $loc = $interview->getLocation();
            if (is_string($loc) && trim($loc) !== '') {
                $lines[] = 'Lieu : ' . trim($loc);
            }
        }

        if ($note !== null && trim($note) !== '') {
            $lines[] = 'Note : ' . trim($note);
        }

        $lines[] = '';
        $lines[] = 'Merci de vous presenter a l heure.';
        $lines[] = 'Bonne chance !';
        $lines[] = '';
        $lines[] = '— Skilora Recruitment';

        return implode("\n", $lines);
    }
}

<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Recruitment\Entity\JobInterview;

final readonly class InterviewScheduleResult
{
    public function __construct(
        public JobInterview $interview,
        public InterviewWhatsAppDispatchResult $whatsApp,
    ) {
    }
}


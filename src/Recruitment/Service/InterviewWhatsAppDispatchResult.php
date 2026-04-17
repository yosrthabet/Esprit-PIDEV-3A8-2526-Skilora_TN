<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

final readonly class InterviewWhatsAppDispatchResult
{
    public function __construct(
        public string $status,
        public ?string $detail = null,
        public ?string $recipient = null,
    ) {
    }
}


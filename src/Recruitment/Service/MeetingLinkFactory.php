<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

class MeetingLinkFactory
{
    public function generateJitsiLink(int $applicationId): string
    {
        return sprintf('https://meet.jit.si/skilora-%d-%s', $applicationId, bin2hex(random_bytes(4)));
    }
}

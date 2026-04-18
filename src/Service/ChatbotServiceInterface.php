<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Formation\FormationChatbotAnswer;

interface ChatbotServiceInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function answer(string $message, array $context): FormationChatbotAnswer;
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Formation\FormationChatbotAnswer;

/**
 * Formation catalogue assistant — keyword engine today; swap implementation for OpenAI later.
 */
interface ChatbotServiceInterface
{
    /**
     * @param array<string, mixed> $context Normalized client context (filters, optional visible cards).
     */
    public function answer(string $message, array $context): FormationChatbotAnswer;
}

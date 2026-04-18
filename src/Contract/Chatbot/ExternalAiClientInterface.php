<?php

declare(strict_types=1);

namespace App\Contract\Chatbot;

/**
 * Optional LLM backend (OpenAI-compatible API, e.g. Groq) for catalogue answers.
 */
interface ExternalAiClientInterface
{
    /**
     * @param array<string, mixed> $context Keys: search_query, active_category, filter_level, visible_formations, etc.
     */
    public function complete(string $userMessage, array $context): ?string;
}

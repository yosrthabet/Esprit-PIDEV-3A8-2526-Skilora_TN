<?php

declare(strict_types=1);

namespace App\Contract\Chatbot;

/**
 * Optional hook for a future OpenAI / LLM integration.
 * When implemented and registered as a service, {@see \App\Service\Chatbot\ChatbotService}
 * can delegate to it before falling back to the keyword engine.
 */
interface ExternalAiClientInterface
{
    /**
     * @param array<string, mixed> $context Same payload as the HTTP chatbot context (filters, locale hints, etc.)
     */
    public function complete(string $userMessage, array $context): ?string;
}

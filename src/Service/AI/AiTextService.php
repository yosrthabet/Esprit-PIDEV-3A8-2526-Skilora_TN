<?php

declare(strict_types=1);

namespace App\Service\AI;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AiTextService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $chatbotApiUrl,
        private readonly string $chatbotApiKey,
        private readonly string $chatbotModel,
    ) {
    }

    public function correctText(string $text): ?string
    {
        if (mb_strlen($text) < 5) {
            return $text;
        }

        $result = $this->ask('You are a professional editor. Correct only the grammar and spelling of the following text. Keep the original meaning, intent, and language. Do NOT add explanations, commentary, or extra sentences. Respond ONLY with the corrected version of the text, nothing else.', $text);

        if ($result === null) {
            return null;
        }

        $resultLen = mb_strlen($result);
        $textLen = mb_strlen($text);

        if ($resultLen > $textLen * 3) {
            return $text;
        }

        return $result;
    }

    public function suggestSubject(string $description): ?string
    {
        if (mb_strlen($description) < 10) {
            return null;
        }

        return $this->ask('Based on the following support ticket description, suggest a concise and professional subject line (maximum 60 characters). Respond ONLY with the suggested subject line, nothing else.', $description);
    }

    public function detectTone(string $text): string
    {
        if (mb_strlen($text) < 5) {
            return 'Neutral';
        }

        $result = $this->ask('Analyze the emotional sentiment of the following message. Classify it as exactly one of these words: Friendly, Professional, Frustrated, Angry, Sad, Urgent, or Neutral. Respond with ONLY the word.', $text);

        return trim($result ?? 'Neutral');
    }

    public function translate(string $text, string $targetLang = 'English'): ?string
    {
        if (mb_strlen($text) < 2) {
            return $text;
        }

        return $this->ask("Translate the following text into {$targetLang}. Respond ONLY with the translated text, nothing else.", $text);
    }

    public function summarizeMessages(array $messages): ?string
    {
        if (count($messages) < 2) {
            return null;
        }

        $text = implode("\n", $messages);

        return $this->ask('Summarize the following conversation in 2-3 concise sentences. Keep it neutral and informative. Respond in the same language as the messages.', $text, 400);
    }

    private function ask(string $system, string $user, int $maxTokens = 300): ?string
    {
        if ($this->chatbotApiKey === '' || $this->chatbotApiUrl === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', $this->chatbotApiUrl, [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->chatbotApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->chatbotModel ?: 'llama-3.3-70b-versatile',
                    'temperature' => 0.2,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ],
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? null;

            return is_string($content) && trim($content) !== '' ? trim($content) : null;
        } catch (\Throwable $e) {
            $this->logger->warning('ai_text.error', ['error' => $e->getMessage()]);

            return null;
        }
    }
}

<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $apiKey = 'xxxxxxxxxxxxxxxxxxxxxxx'
    ) {
        $this->apiKey = $apiKey;
    }

    public function suggestSubject(string $description): ?string
    {
        if (strlen($description) < 10)
            return null;

        $prompt = "Based on the following support ticket description, suggest a concise and professional subject line (maximum 50 characters). Respond ONLY with the suggested subject line, nothing else: \n\n" . $description;

        return $this->callGemini($prompt);
    }

    public function correctText(string $text): ?string
    {
        if (strlen($text) < 5)
            return $text;

        $prompt = "You are a professional editor. Correct the grammar and spelling of the following text while keeping its original meaning. Respond ONLY with the corrected text, nothing else: \n\n" . $text;

        return $this->callGemini($prompt);
    }

    public function detectTone(string $text): ?string
    {
        if (strlen($text) < 5) return 'Neutral';
        
        $prompt = "Analyze the emotional sentiment of the following support message. Classify it as exactly one of these words: Friendly, Professional, Frustrated, Angry, Sad, Urgent, or Neutral. Respond with ONLY the word: \n\n" . $text;
        return trim($this->callGemini($prompt) ?? 'Neutral');
    }

    public function translate(string $text, string $targetLang = 'English'): ?string
    {
        if (strlen($text) < 2)
            return $text;

        $prompt = "Translate the following text into {$targetLang}. Respond ONLY with the translated text, nothing else: \n\n" . $text;
        return $this->callGemini($prompt);
    }

    private function callGemini(string $prompt): ?string
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL . '?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']
                    ]
                ]
            ]);

            $data = $response->toArray();
            
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                // If it was blocked, the reason might be here
                return null;
            }

            return $data['candidates'][0]['content']['parts'][0]['text'];
        } catch (\Exception $e) {
            return null;
        }
    }
}

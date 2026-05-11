<?php

declare(strict_types=1);

namespace App\Community\Service;

use App\Service\AI\SkiloraMlClient;

class CommunityAiAssistant
{
    public function __construct(private readonly SkiloraMlClient $mlClient)
    {
    }

    /** @return array{sentiment: string, confidence: float, tone: string, toxic: bool, summary: string} */
    public function analyzeTone(string $content): array
    {
        $result = $this->mlClient->analyzeSentiment(['content' => $content]);
        $sentiment = $result['sentiment'] ?? null;
        $confidence = $result['confidence'] ?? null;
        $tone = $result['tone'] ?? null;
        $summary = $result['summary'] ?? null;

        return [
            'sentiment' => is_string($sentiment) ? $sentiment : 'neutral',
            'confidence' => is_numeric($confidence) ? (float) $confidence : 0.0,
            'tone' => is_string($tone) ? $tone : 'unknown',
            'toxic' => (bool) ($result['toxic'] ?? false),
            'summary' => is_string($summary) ? $summary : $this->fallbackSummary($content),
        ];
    }

    public function improve(string $content): string
    {
        $result = $this->mlClient->analyzeSentiment([
            'content' => $content,
            'task' => 'improve',
            'style' => 'professional, concise, warm, and engaging',
        ]);

        $improvedValue = $result['improved'] ?? $result['text'] ?? null;
        $improved = is_string($improvedValue) ? trim($improvedValue) : '';
        if ($improved !== '') {
            return $improved;
        }

        return $this->fallbackImprove($content);
    }

    private function fallbackImprove(string $content): string
    {
        $content = preg_replace('/\s+/', ' ', trim($content)) ?? trim($content);
        if ($content === '') {
            return '';
        }

        return ucfirst($content);
    }

    private function fallbackSummary(string $content): string
    {
        $content = trim(preg_replace('/\s+/', ' ', $content) ?? $content);

        return mb_substr($content, 0, 140);
    }
}

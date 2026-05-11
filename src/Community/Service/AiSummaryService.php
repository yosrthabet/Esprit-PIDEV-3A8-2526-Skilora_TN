<?php

declare(strict_types=1);

namespace App\Community\Service;

use App\Service\AI\SkiloraMlClient;

class AiSummaryService
{
    public function __construct(private readonly SkiloraMlClient $mlClient)
    {
    }

    public function summarize(string $content, int $maxSentences = 3): string
    {
        $result = $this->mlClient->analyzeSentiment([
            'content' => $content,
            'task' => 'summarize',
            'max_sentences' => $maxSentences,
        ]);

        return is_string($result['summary'] ?? null) ? $result['summary'] : mb_substr($content, 0, 200) . '…';
    }
}

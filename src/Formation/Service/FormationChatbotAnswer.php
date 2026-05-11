<?php

declare(strict_types=1);

namespace App\Formation\Service;

final readonly class FormationChatbotAnswer
{
    /**
     * @param list<array<string, mixed>> $formations
     */
    public function __construct(
        public string $reply,
        public string $intent,
        public array $formations = [],
        public bool $fallback = false,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'reply' => $this->reply,
            'intent' => $this->intent,
            'formations' => $this->formations,
            'results' => $this->formations,
            'fallback' => $this->fallback,
        ];
    }
}

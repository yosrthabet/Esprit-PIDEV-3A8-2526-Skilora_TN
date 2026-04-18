<?php

declare(strict_types=1);

namespace App\Service\Formation;

/**
 * Response payload for the formations catalog chatbot (JSON-serializable).
 *
 * @phpstan-type FormationHighlight array{
 *   id: int,
 *   title: string,
 *   categoryLabel: string,
 *   levelLabel: string,
 *   durationHours: int|null,
 *   priceLabel: string,
 *   currency: string|null,
 *   isFree: bool|null,
 *   averageRating: float,
 *   reviewCount: int,
 *   url: string
 * }
 */
final readonly class FormationChatbotAnswer
{
    /**
     * @param list<FormationHighlight> $formations
     */
    public function __construct(
        public string $reply,
        public string $intent,
        public array $formations = [],
        public bool $fallback = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
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

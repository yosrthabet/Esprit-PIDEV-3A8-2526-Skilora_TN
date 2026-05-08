<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class NoBadWords extends Constraint
{
    public string $message = 'The content contains forbidden words: "{{ words }}".';

    /** @var list<string> */
    public array $badWords = [
        'badword1', 'badword2', 'offensive', 'spam', 'scam', 'hate', 'stupid', 'idiot'
    ];

    /**
     * @param array<string, mixed>|null $options
     * @param list<string>|null $badWords
     * @param list<string>|null $groups
     */
    public function __construct(
        ?array $options = null,
        ?array $badWords = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options ?? [], $groups, $payload);

        $this->badWords = $badWords ?? $this->badWords;
    }
}

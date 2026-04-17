<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class NoBadWords extends Constraint
{
    public string $message = 'The content contains forbidden words: "{{ words }}".';
    
    // You can pass a custom list of words if needed
    public array $badWords = [
        'badword1', 'badword2', 'offensive', 'spam', 'scam', 'hate', 'stupid', 'idiot'
    ];

    public function __construct(
        array $options = null,
        array $badWords = null,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options ?? [], $groups, $payload);

        $this->badWords = $badWords ?? $this->badWords;
    }
}

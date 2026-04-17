<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures total duration (hours) is strictly greater than the number of lessons
 * (business rule: avoids impossible schedules like 10 lessons in 5 hours for this product).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class FormationDurationConsistent extends Constraint
{
    public string $message = 'formation.validation.duration_vs_lessons';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

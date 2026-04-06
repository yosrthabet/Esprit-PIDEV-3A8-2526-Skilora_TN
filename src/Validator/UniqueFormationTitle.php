<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * No duplicate formation title (trimmed, case-insensitive) for create and update (excluding current id).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniqueFormationTitle extends Constraint
{
    public string $message = 'formation.validation.duplicate_title';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

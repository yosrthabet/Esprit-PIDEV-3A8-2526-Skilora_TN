<?php

declare(strict_types=1);

namespace App\Formation\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class FormationDurationConsistent extends Constraint
{
    public string $message = 'Duration must be at least {{ min }} hours for {{ level }}-level formations.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

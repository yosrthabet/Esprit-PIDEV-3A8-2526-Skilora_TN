<?php

declare(strict_types=1);

namespace App\Formation\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UniqueFormationTitle extends Constraint
{
    public string $message = 'A formation with the title "{{ title }}" already exists.';
}

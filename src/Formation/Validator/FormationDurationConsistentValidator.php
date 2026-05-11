<?php

declare(strict_types=1);

namespace App\Formation\Validator;

use App\Formation\Entity\Formation;
use App\Formation\FormationLevel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FormationDurationConsistentValidator extends ConstraintValidator
{
    private const MIN_HOURS = [
        'beginner' => 2,
        'intermediate' => 4,
        'advanced' => 8,
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FormationDurationConsistent) {
            throw new UnexpectedTypeException($constraint, FormationDurationConsistent::class);
        }
        if (!$value instanceof Formation) {
            return;
        }

        $level = $value->getLevel();
        $minHours = self::MIN_HOURS[$level->value] ?? 0;

        if ($value->getDurationHours() < $minHours) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ min }}', (string) $minHours)
                ->setParameter('{{ level }}', $level->value)
                ->atPath('durationHours')
                ->addViolation();
        }
    }
}

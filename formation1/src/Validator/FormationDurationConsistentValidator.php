<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Formation;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class FormationDurationConsistentValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FormationDurationConsistent) {
            throw new UnexpectedTypeException($constraint, FormationDurationConsistent::class);
        }

        if (!$value instanceof Formation) {
            return;
        }

        $duration = $value->getDuration();
        $lessons = $value->getLessonsCount();

        if (null === $duration || null === $lessons) {
            return;
        }

        if ($duration > $lessons) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ duration }}', (string) $duration)
            ->setParameter('{{ lessons }}', (string) $lessons)
            ->atPath('duration')
            ->addViolation();
    }
}

<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Formation;
use App\Repository\FormationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueFormationTitleValidator extends ConstraintValidator
{
    public function __construct(
        private readonly FormationRepository $formationRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueFormationTitle) {
            throw new UnexpectedTypeException($constraint, UniqueFormationTitle::class);
        }

        if (!$value instanceof Formation) {
            return;
        }

        $title = trim($value->getTitle());
        if ('' === $title || \strlen($title) < 3) {
            return;
        }

        $other = $this->formationRepository->findAnotherWithSameTitle($title, $value->getId());
        if (null !== $other) {
            $this->context->buildViolation($constraint->message)
                ->atPath('title')
                ->addViolation();
        }
    }
}

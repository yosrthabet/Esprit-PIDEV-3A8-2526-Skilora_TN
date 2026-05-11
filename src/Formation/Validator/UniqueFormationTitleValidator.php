<?php

declare(strict_types=1);

namespace App\Formation\Validator;

use App\Formation\Repository\FormationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueFormationTitleValidator extends ConstraintValidator
{
    public function __construct(private readonly FormationRepository $formationRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueFormationTitle) {
            throw new UnexpectedTypeException($constraint, UniqueFormationTitle::class);
        }
        if (!is_string($value) || $value === '') {
            return;
        }

        $existing = $this->formationRepository->findOneBy(['title' => $value]);
        if ($existing !== null) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ title }}', $value)
                ->addViolation();
        }
    }
}

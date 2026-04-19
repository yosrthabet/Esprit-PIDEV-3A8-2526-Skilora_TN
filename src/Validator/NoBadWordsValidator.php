<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NoBadWordsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoBadWords) {
            throw new UnexpectedTypeException($constraint, NoBadWords::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $foundWords = [];
        foreach ($constraint->badWords as $badWord) {
            if (preg_match('/\b' . preg_quote($badWord, '/') . '\b/i', $value)) {
                $foundWords[] = $badWord;
            }
        }

        if (count($foundWords) > 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ words }}', implode(', ', $foundWords))
                ->addViolation();
        }
    }
}

<?php

namespace App\Tests\Validator;

use App\Validator\NoBadWords;
use App\Validator\NoBadWordsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NoBadWordsValidatorTest extends TestCase
{
    private NoBadWordsValidator $validator;
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        $this->validator = new NoBadWordsValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testCleanTextPasses(): void
    {
        $constraint = new NoBadWords();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('This is a perfectly fine message', $constraint);
    }

    public function testBadWordDetected(): void
    {
        $constraint = new NoBadWords();

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate('You are an idiot', $constraint);
    }

    public function testCaseInsensitive(): void
    {
        $constraint = new NoBadWords();

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->validator->validate('SPAM is bad', $constraint);
    }

    public function testNullValueSkipped(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, new NoBadWords());
    }

    public function testEmptyStringSkipped(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', new NoBadWords());
    }

    public function testCustomBadWordsList(): void
    {
        $constraint = new NoBadWords(badWords: ['forbidden', 'blocked']);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->validator->validate('This word is forbidden', $constraint);
    }

    public function testCustomBadWordsDoNotMatchDefault(): void
    {
        $constraint = new NoBadWords(badWords: ['forbidden']);

        // "idiot" is in defaults but NOT in custom list
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('You are an idiot', $constraint);
    }

    public function testWordBoundaryMatching(): void
    {
        $constraint = new NoBadWords(badWords: ['hate']);

        // "whatever" contains "hate" but not as a word boundary
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('I whatever you say', $constraint);
    }

    public function testMultipleBadWordsDetected(): void
    {
        $constraint = new NoBadWords();

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setParameter')
            ->with('{{ words }}', $this->callback(function ($value) {
                return str_contains($value, 'spam') && str_contains($value, 'scam');
            }))
            ->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->validator->validate('This is spam and a scam', $constraint);
    }
}

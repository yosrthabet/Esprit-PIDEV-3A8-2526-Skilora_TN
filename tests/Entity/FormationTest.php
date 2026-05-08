<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Formation\Entity\Formation;
use App\Formation\Entity\FormationMaterial;
use App\Formation\Entity\FormationModule;
use App\Formation\Entity\FormationReview;
use App\Formation\FormationLevel;
use App\Formation\FormationStatus;
use PHPUnit\Framework\TestCase;

final class FormationTest extends TestCase
{
    public function testFormationDefaultsAndModuleLinking(): void
    {
        $trainer = (new User())->setUsername('trainer')->setRole('TRAINER');
        $module = (new FormationModule())
            ->setTitle('Introduction')
            ->setPosition(-5)
            ->setDurationMinutes(45);

        $formation = (new Formation())
            ->setTrainer($trainer)
            ->setTitle('Symfony Basics')
            ->setCategory('Web')
            ->setLevel(FormationLevel::BEGINNER)
            ->setDurationHours(12)
            ->addModule($module);

        self::assertSame($trainer, $formation->getTrainer());
        self::assertSame(FormationStatus::DRAFT, $formation->getStatus());
        self::assertSame(FormationLevel::BEGINNER, $formation->getLevel());
        self::assertSame(0, $module->getPosition());
        self::assertSame($formation, $module->getFormation());
        self::assertCount(1, $formation->getModules());
        $formation->removeModule($module);
        self::assertCount(0, $formation->getModules());
    }

    public function testFormationMaterialAndReviewClampValues(): void
    {
        $learner = (new User())->setUsername('learner')->setRole('USER');
        $formation = (new Formation())->setTrainer((new User())->setUsername('trainer')->setRole('TRAINER'));
        $module = (new FormationModule())->setTitle('Resources');
        $material = (new FormationMaterial())
            ->setTitle('Slides')
            ->setKind('slides')
            ->setResourceUrl('https://example.test/slides')
            ->setPosition(-3);
        $review = (new FormationReview())
            ->setFormation($formation)
            ->setUser($learner)
            ->setRating(9)
            ->setComment('Useful.');

        $module->addMaterial($material);

        self::assertSame($module, $material->getModule());
        self::assertSame(0, $material->getPosition());
        self::assertSame(5, $review->getRating());
        self::assertSame('Useful.', $review->getComment());
    }
}

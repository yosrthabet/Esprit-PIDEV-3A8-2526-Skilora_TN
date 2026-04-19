<?php

namespace App\Tests\Entity;

use App\Entity\Formation;
use App\Enum\FormationLevel;
use PHPUnit\Framework\TestCase;

class FormationTest extends TestCase
{
    private function createFormation(array $o = []): Formation
    {
        $f = new Formation();
        $f->setTitle($o['title'] ?? 'Symfony Basics');
        $f->setDescription($o['description'] ?? 'Learn Symfony');
        $f->setCategory($o['category'] ?? 'DEVELOPMENT');
        $f->setDuration($o['duration'] ?? 40);
        $f->setLessonsCount($o['lessons'] ?? 10);
        $f->setLevel($o['level'] ?? FormationLevel::BEGINNER);
        $f->setPrice($o['price'] ?? 99.99);
        $f->setCurrency($o['currency'] ?? 'TND');
        $f->setProvider($o['provider'] ?? 'Skilora');
        $f->setStatus($o['status'] ?? 'ACTIVE');
        $f->setIsFree($o['isFree'] ?? false);

        return $f;
    }

    public function testGettersReturnSetValues(): void
    {
        $f = $this->createFormation();

        $this->assertSame('Symfony Basics', $f->getTitle());
        $this->assertSame('Learn Symfony', $f->getDescription());
        $this->assertSame('DEVELOPMENT', $f->getCategory());
        $this->assertSame(40, $f->getDuration());
        $this->assertSame(10, $f->getLessonsCount());
        $this->assertSame(FormationLevel::BEGINNER, $f->getLevel());
        $this->assertSame(99.99, $f->getPrice());
        $this->assertSame('TND', $f->getCurrency());
        $this->assertSame('Skilora', $f->getProvider());
        $this->assertSame('ACTIVE', $f->getStatus());
        $this->assertFalse($f->isFree());
    }

    public function testNewFormationIdIsNull(): void
    {
        $this->assertNull((new Formation())->getId());
    }

    public function testLevelLabelFr(): void
    {
        $f = $this->createFormation(['level' => FormationLevel::INTERMEDIATE]);
        $this->assertSame('Intermédiaire', $f->getLevelLabelFr());
    }

    public function testPriceDisplayFrForFree(): void
    {
        $f = $this->createFormation(['isFree' => true, 'price' => 0.0]);
        $this->assertStringContainsString('Gratuit', $f->getPriceDisplayFr());
    }

    public function testCategoryKeys(): void
    {
        $keys = Formation::getCategoryKeys();
        $this->assertIsArray($keys);
        $this->assertNotEmpty($keys);
    }

    public function testNullableFields(): void
    {
        $f = new Formation();
        $this->assertNull($f->getDescription());
        $this->assertNull($f->getPrice());
        $this->assertNull($f->getProvider());
        $this->assertNull($f->getImageUrl());
        $this->assertNull($f->getDirectorSignature());
    }

    public function testImageUrl(): void
    {
        $f = new Formation();
        $f->setImageUrl('https://img.test/course.jpg');
        $this->assertSame('https://img.test/course.jpg', $f->getImageUrl());
    }

    public function testCreatedByField(): void
    {
        $f = new Formation();
        $f->setCreatedBy(42);
        $this->assertSame(42, $f->getCreatedBy());
    }
}

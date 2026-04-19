<?php

namespace App\Tests\Entity;

use App\Entity\BlogArticle;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BlogArticleTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('author');
        $u->setEmail('author@test.com');
        $u->setFullName('Author');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new BlogArticle())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $a = new BlogArticle();
        $author = $this->makeUser();
        $a->setAuthor($author);
        $a->setTitle('My Article');
        $a->setContent('Article content here.');
        $a->setSummary('Short summary');
        $a->setCoverImageUrl('https://img.test/cover.jpg');
        $a->setCategory('Tech');
        $a->setTags('php,symfony');

        $this->assertSame($author, $a->getAuthor());
        $this->assertSame('My Article', $a->getTitle());
        $this->assertSame('Article content here.', $a->getContent());
        $this->assertSame('Short summary', $a->getSummary());
        $this->assertSame('https://img.test/cover.jpg', $a->getCoverImageUrl());
        $this->assertSame('Tech', $a->getCategory());
        $this->assertSame('php,symfony', $a->getTags());
    }

    public function testDefaultValues(): void
    {
        $a = new BlogArticle();
        $this->assertSame(0, $a->getViewsCount());
        $this->assertSame(0, $a->getLikesCount());
        $this->assertFalse($a->isPublished());
        $this->assertNull($a->getPublishedDate());
    }

    public function testPublishWorkflow(): void
    {
        $a = new BlogArticle();
        $a->setIsPublished(true);
        $date = new \DateTimeImmutable('2026-01-01');
        $a->setPublishedDate($date);

        $this->assertTrue($a->isPublished());
        $this->assertSame($date, $a->getPublishedDate());
    }

    public function testCounters(): void
    {
        $a = new BlogArticle();
        $a->setViewsCount(42);
        $a->setLikesCount(7);

        $this->assertSame(42, $a->getViewsCount());
        $this->assertSame(7, $a->getLikesCount());
    }

    public function testTimestampsSetInConstructor(): void
    {
        $a = new BlogArticle();
        $this->assertInstanceOf(\DateTimeImmutable::class, $a->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $a->getUpdatedAt());
    }
}

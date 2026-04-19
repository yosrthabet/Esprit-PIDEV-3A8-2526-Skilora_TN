<?php

namespace App\Tests\Entity;

use App\Entity\CommunityPost;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CommunityPostTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('poster');
        $u->setEmail('post@test.com');
        $u->setFullName('Poster');
        $u->setRole('USER');
        $u->setPassword('p');

        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new CommunityPost())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $p = new CommunityPost();
        $author = $this->makeUser();
        $p->setAuthor($author);
        $p->setContent('Hello World!');
        $p->setImageUrl('https://img.test/post.jpg');

        $this->assertSame($author, $p->getAuthor());
        $this->assertSame('Hello World!', $p->getContent());
        $this->assertSame('https://img.test/post.jpg', $p->getImageUrl());
    }

    public function testTimestampsSetInConstructor(): void
    {
        $p = new CommunityPost();
        $this->assertInstanceOf(\DateTimeImmutable::class, $p->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $p->getUpdatedAt());
    }

    public function testNullImageUrl(): void
    {
        $p = new CommunityPost();
        $this->assertNull($p->getImageUrl());
    }

    public function testNewFieldsFromMerge(): void
    {
        $p = new CommunityPost();
        $this->assertSame(CommunityPost::TYPE_STATUS, $p->getPostType());
        $this->assertSame(0, $p->getLikesCount());
        $this->assertSame(0, $p->getCommentsCount());
        $this->assertSame(0, $p->getSharesCount());
    }

    public function testPostTypeSetterGetter(): void
    {
        $p = new CommunityPost();
        $p->setPostType('ARTICLE');
        $this->assertSame('ARTICLE', $p->getPostType());
    }

    public function testCounterSetters(): void
    {
        $p = new CommunityPost();
        $p->setLikesCount(5);
        $p->setCommentsCount(3);
        $p->setSharesCount(1);

        $this->assertSame(5, $p->getLikesCount());
        $this->assertSame(3, $p->getCommentsCount());
        $this->assertSame(1, $p->getSharesCount());
    }

    public function testCommentsAndLikesCollections(): void
    {
        $p = new CommunityPost();
        $this->assertCount(0, $p->getComments());
        $this->assertCount(0, $p->getLikes());
    }
}

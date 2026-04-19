<?php

namespace App\Tests\Entity;

use App\Entity\CommunityPost;
use App\Entity\PostComment;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PostCommentTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('commenter');
        $u->setEmail('commenter@test.com');
        $u->setFullName('Commenter');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new PostComment())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $c = new PostComment();
        $post = new CommunityPost();
        $author = $this->makeUser();

        $c->setPost($post);
        $c->setAuthor($author);
        $c->setContent('Great post!');

        $this->assertSame($post, $c->getPost());
        $this->assertSame($author, $c->getAuthor());
        $this->assertSame('Great post!', $c->getContent());
    }

    public function testTimestampSetInConstructor(): void
    {
        $c = new PostComment();
        $this->assertInstanceOf(\DateTimeImmutable::class, $c->getCreatedAt());
    }
}

<?php

namespace App\Tests\Entity;

use App\Entity\CommunityPost;
use App\Entity\PostLike;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PostLikeTest extends TestCase
{
    private function makeUser(): User
    {
        $u = new User();
        $u->setUsername('liker');
        $u->setEmail('liker@test.com');
        $u->setFullName('Liker');
        $u->setRole('USER');
        $u->setPassword('p');
        return $u;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new PostLike())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $l = new PostLike();
        $post = new CommunityPost();
        $user = $this->makeUser();

        $l->setPost($post);
        $l->setUser($user);

        $this->assertSame($post, $l->getPost());
        $this->assertSame($user, $l->getUser());
    }

    public function testTimestampSetInConstructor(): void
    {
        $l = new PostLike();
        $this->assertInstanceOf(\DateTimeImmutable::class, $l->getCreatedAt());
    }
}

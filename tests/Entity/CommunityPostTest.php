<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Community\CommunityPostStatus;
use App\Community\Entity\CommunityComment;
use App\Community\Entity\CommunityLike;
use App\Community\Entity\CommunityPost;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class CommunityPostTest extends TestCase
{
    public function testPostDefaultsAndCounters(): void
    {
        $author = (new User())->setUsername('learner')->setRole('USER');
        $post = (new CommunityPost())
            ->setAuthor($author)
            ->setContent('  Hello community  ');

        self::assertSame($author, $post->getAuthor());
        self::assertSame('Hello community', $post->getContent());
        self::assertSame(CommunityPostStatus::PUBLISHED, $post->getStatus());
        self::assertTrue($post->isVisible());

        $post->incrementLikes();
        $post->decrementLikes();
        $post->decrementLikes();
        $post->incrementComments();

        self::assertSame(0, $post->getLikesCount());
        self::assertSame(1, $post->getCommentsCount());
    }

    public function testCommentAndLikeLinking(): void
    {
        $author = (new User())->setUsername('learner')->setRole('USER');
        $post = (new CommunityPost())->setAuthor($author)->setContent('Thread');
        $comment = (new CommunityComment())
            ->setPost($post)
            ->setAuthor($author)
            ->setContent('  Reply  ');
        $like = (new CommunityLike())->setPost($post)->setUser($author);

        self::assertSame($post, $comment->getPost());
        self::assertSame('Reply', $comment->getContent());
        self::assertSame($post, $like->getPost());
        self::assertSame($author, $like->getUser());
    }
}

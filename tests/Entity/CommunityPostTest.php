<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Community\CommunityPostStatus;
use App\Community\CommunityPostVisibility;
use App\Community\CommunityReactionType;
use App\Community\Entity\CommunityBookmark;
use App\Community\Entity\CommunityComment;
use App\Community\Entity\CommunityLike;
use App\Community\Entity\CommunityPost;
use App\Community\Entity\CommunityReaction;
use App\Community\Entity\CommunityReport;
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
        self::assertSame(CommunityPostVisibility::PUBLIC, $post->getVisibility());
        self::assertTrue($post->isVisible());

        $post->incrementLikes();
        $post->decrementLikes();
        $post->decrementLikes();
        $post->incrementComments();
        $post->incrementShares();
        $post->incrementReports();

        self::assertSame(0, $post->getLikesCount());
        self::assertSame(1, $post->getCommentsCount());
        self::assertSame(1, $post->getSharesCount());
        self::assertSame(1, $post->getReportsCount());
    }

    public function testCommentAndLikeLinking(): void
    {
        $author = (new User())->setUsername('learner')->setRole('USER');
        $post = (new CommunityPost())->setAuthor($author)->setContent('Thread');
        $comment = (new CommunityComment())
            ->setPost($post)
            ->setAuthor($author)
            ->setContent('  Reply  ');
        $reply = (new CommunityComment())
            ->setPost($post)
            ->setAuthor($author)
            ->setContent('Nested')
            ->setParent($comment);
        $like = (new CommunityLike())->setPost($post)->setUser($author);
        $reaction = (new CommunityReaction())->setPost($post)->setUser($author)->setType(CommunityReactionType::FIRE);
        $bookmark = (new CommunityBookmark())->setPost($post)->setUser($author);
        $report = (new CommunityReport())->setPost($post)->setReporter($author)->setReason('Spam');

        self::assertSame($post, $comment->getPost());
        self::assertSame('Reply', $comment->getContent());
        self::assertSame($comment, $reply->getParent());
        self::assertTrue($reply->isReply());
        self::assertSame($post, $like->getPost());
        self::assertSame($author, $like->getUser());
        self::assertSame(CommunityReactionType::FIRE, $reaction->getType());
        self::assertSame($post, $bookmark->getPost());
        self::assertSame('Spam', $report->getReason());
    }
}

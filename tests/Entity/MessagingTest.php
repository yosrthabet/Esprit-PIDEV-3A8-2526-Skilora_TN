<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Messaging\Entity\DmConversation;
use App\Messaging\Entity\DmMessage;
use PHPUnit\Framework\TestCase;

final class MessagingTest extends TestCase
{
    public function testConversationParticipantsAndMessages(): void
    {
        $one = (new User())->setUsername('one')->setRole('USER');
        $two = (new User())->setUsername('two')->setRole('TRAINER');
        $conversation = new DmConversation();
        $first = $conversation->addParticipant($one);
        $second = $conversation->addParticipant($two);
        $message = (new DmMessage())->setSender($one)->setBody('  Hello  ');

        $conversation->addMessage($message);
        $second->incrementUnread();
        $second->markRead();

        self::assertSame($conversation, $first->getConversation());
        self::assertSame($two, $conversation->otherParticipant($one));
        self::assertSame($conversation, $message->getConversation());
        self::assertSame('Hello', $message->getBody());
        self::assertSame(0, $second->getUnreadCount());
    }
}

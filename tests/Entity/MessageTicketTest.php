<?php

namespace App\Tests\Entity;

use App\Entity\MessageTicket;
use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;

class MessageTicketTest extends TestCase
{
    private function createMessage(array $o = []): MessageTicket
    {
        $m = new MessageTicket();
        $m->setSenderId($o['senderId'] ?? 1);
        $m->setMessage($o['message'] ?? 'Test message');
        $m->setCreatedDate($o['createdDate'] ?? new \DateTime());

        return $m;
    }

    public function testNewMessageIdIsNull(): void
    {
        $this->assertNull((new MessageTicket())->getId());
    }

    public function testGettersReturnSetValues(): void
    {
        $m = $this->createMessage([
            'senderId' => 5,
            'message' => 'Hello world',
        ]);

        $this->assertSame(5, $m->getSenderId());
        $this->assertSame('Hello world', $m->getMessage());
    }

    public function testTicketAssociation(): void
    {
        $t = new Ticket();
        $m = $this->createMessage();
        $m->setTicket($t);

        $this->assertSame($t, $m->getTicket());
    }

    public function testIsInternalDefaultsFalse(): void
    {
        $m = new MessageTicket();
        $this->assertFalse($m->isInternal());
    }

    public function testSetIsInternal(): void
    {
        $m = $this->createMessage();
        $m->setIsInternal(true);
        $this->assertTrue($m->isInternal());
    }

    public function testAttachmentsJson(): void
    {
        $m = $this->createMessage();
        $this->assertNull($m->getAttachmentsJson());

        $m->setAttachmentsJson('["file1.pdf","file2.png"]');
        $this->assertSame('["file1.pdf","file2.png"]', $m->getAttachmentsJson());
    }

    public function testAudioPath(): void
    {
        $m = $this->createMessage();
        $this->assertNull($m->getAudioPath());

        $m->setAudioPath('/uploads/audio/msg1.mp3');
        $this->assertSame('/uploads/audio/msg1.mp3', $m->getAudioPath());
    }

    public function testIsAudio(): void
    {
        $m = $this->createMessage();
        $this->assertNull($m->isAudio());

        $m->setIsAudio(true);
        $this->assertTrue($m->isAudio());
    }

    public function testSentimentField(): void
    {
        $m = $this->createMessage();
        $this->assertNull($m->getSentiment());

        $m->setSentiment('positive');
        $this->assertSame('positive', $m->getSentiment());
    }

    public function testSentimentNullable(): void
    {
        $m = $this->createMessage();
        $m->setSentiment('angry');
        $m->setSentiment(null);
        $this->assertNull($m->getSentiment());
    }

    public function testCreatedDate(): void
    {
        $date = new \DateTime('2026-04-19');
        $m = $this->createMessage(['createdDate' => $date]);
        $this->assertSame($date, $m->getCreatedDate());
    }
}

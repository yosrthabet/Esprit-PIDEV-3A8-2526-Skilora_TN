<?php

namespace App\Tests\Entity;

use App\Entity\Feedback;
use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;

class FeedbackTest extends TestCase
{
    private function makeTicket(): Ticket
    {
        $t = new Ticket();
        $t->setUserId(1);
        $t->setSubject('Test');
        $t->setCategory('TECHNIQUE');
        $t->setPriority('LOW');
        $t->setDescription('Test ticket');
        $t->setStatus('OPEN');

        return $t;
    }

    public function testNewIdIsNull(): void
    {
        $this->assertNull((new Feedback())->getId());
    }

    public function testGettersAndSetters(): void
    {
        $fb = new Feedback();
        $ticket = $this->makeTicket();
        $fb->setTicket($ticket);
        $fb->setUserId(5);
        $fb->setRating(4);
        $fb->setComment('Great support');
        $fb->setFeedbackType('SATISFACTION');
        $fb->setCategory('SUPPORT');
        $fb->setIsResolved(true);

        $this->assertSame($ticket, $fb->getTicket());
        $this->assertSame(5, $fb->getUserId());
        $this->assertSame(4, $fb->getRating());
        $this->assertSame('Great support', $fb->getComment());
        $this->assertSame('SATISFACTION', $fb->getFeedbackType());
        $this->assertSame('SUPPORT', $fb->getCategory());
        $this->assertTrue($fb->isResolved());
    }

    public function testNullDefaults(): void
    {
        $fb = new Feedback();
        $this->assertNull($fb->getComment());
        $this->assertSame('TICKET', $fb->getFeedbackType());
        $this->assertNull($fb->getCategory());
    }
}

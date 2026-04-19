<?php

namespace App\Tests\Entity;

use App\Entity\Ticket;
use App\Entity\MessageTicket;
use App\Entity\Feedback;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    private function createTicket(array $o = []): Ticket
    {
        $t = new Ticket();
        $t->setUserId($o['userId'] ?? 1);
        $t->setSubject($o['subject'] ?? 'Login issue');
        $t->setCategory($o['category'] ?? 'TECHNIQUE');
        $t->setPriority($o['priority'] ?? 'MEDIUM');
        $t->setDescription($o['description'] ?? 'Cannot log in');
        $t->setStatus($o['status'] ?? 'OPEN');

        return $t;
    }

    public function testNewTicketIdIsNull(): void
    {
        $this->assertNull((new Ticket())->getId());
    }

    public function testGettersReturnSetValues(): void
    {
        $t = $this->createTicket();

        $this->assertSame(1, $t->getUserId());
        $this->assertSame('Login issue', $t->getSubject());
        $this->assertSame('TECHNIQUE', $t->getCategory());
        $this->assertSame('MEDIUM', $t->getPriority());
        $this->assertSame('Cannot log in', $t->getDescription());
        $this->assertSame('OPEN', $t->getStatus());
    }

    public function testDatesNullByDefault(): void
    {
        $t = new Ticket();
        $this->assertNull($t->getResolvedDate());
        $this->assertNull($t->getSlaDueDate());
        $this->assertNull($t->getFirstResponseDate());
    }

    public function testAssignedTo(): void
    {
        $t = $this->createTicket();
        $t->setAssignedTo(42);
        $this->assertSame(42, $t->getAssignedTo());
    }

    public function testAddMessage(): void
    {
        $t = $this->createTicket();
        $m = new MessageTicket();
        $m->setSenderId(1);
        $m->setMessage('Help me');
        $t->addMessage($m);

        $this->assertCount(1, $t->getMessages());
    }

    public function testAddFeedback(): void
    {
        $t = $this->createTicket();
        $fb = new Feedback();
        $fb->setUserId(1);
        $fb->setRating(5);
        $t->addFeedback($fb);

        $this->assertCount(1, $t->getFeedbacks());
    }

    public function testRemoveMessage(): void
    {
        $t = $this->createTicket();
        $m = new MessageTicket();
        $m->setSenderId(1);
        $m->setMessage('Hello');
        $t->addMessage($m);
        $t->removeMessage($m);

        $this->assertCount(0, $t->getMessages());
    }
}

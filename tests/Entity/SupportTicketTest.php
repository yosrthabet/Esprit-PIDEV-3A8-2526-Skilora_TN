<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Enum\TicketCategory;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Support\Entity\SupportTicket;
use PHPUnit\Framework\TestCase;

class SupportTicketTest extends TestCase
{
    public function testNewTicketHasDefaults(): void
    {
        $ticket = new SupportTicket();
        $this->assertNull($ticket->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getUpdatedAt());
        $this->assertNull($ticket->getResolvedAt());
        $this->assertFalse($ticket->hasFeedback());
    }

    public function testSetStatusResolved(): void
    {
        $ticket = new SupportTicket();
        $ticket->setStatus(TicketStatus::RESOLVED);
        $this->assertSame(TicketStatus::RESOLVED, $ticket->getStatus());
        $this->assertNotNull($ticket->getResolvedAt());
    }

    public function testFeedbackRatingClamped(): void
    {
        $ticket = new SupportTicket();
        $ticket->setFeedbackRating(10);
        $this->assertSame(5, $ticket->getFeedbackRating());

        $ticket->setFeedbackRating(0);
        $this->assertSame(1, $ticket->getFeedbackRating());
    }

    public function testHasFeedback(): void
    {
        $ticket = new SupportTicket();
        $this->assertFalse($ticket->hasFeedback());
        $ticket->setFeedbackRating(4);
        $this->assertTrue($ticket->hasFeedback());
    }

    public function testFeedbackComment(): void
    {
        $ticket = new SupportTicket();
        $ticket->setFeedbackComment('Great support!');
        $this->assertSame('Great support!', $ticket->getFeedbackComment());
    }
}

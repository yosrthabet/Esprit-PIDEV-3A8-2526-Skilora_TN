<?php

namespace App\Tests\Integration;

use App\Entity\Feedback;
use App\Entity\MessageTicket;
use App\Entity\Ticket;

class SupportSyncVerificationTest extends DatabaseTestCase
{
    private function createTicket(): Ticket
    {
        $user = $this->createTestUser();
        $t = new Ticket();
        $t->setUserId($user->getId());
        $t->setSubject('Sync Test Ticket');
        $t->setCategory('TECHNIQUE');
        $t->setPriority('HIGH');
        $t->setDescription('Testing synced features');
        $t->setStatus('OPEN');
        $t->setCreatedDate(new \DateTime());
        $this->em->persist($t);
        $this->em->flush();

        return $t;
    }

    // ── MessageTicket sentiment column ──

    public function testMessageSentimentPersists(): void
    {
        $t = $this->createTicket();
        $m = new MessageTicket();
        $m->setTicket($t);
        $m->setSenderId($t->getUserId());
        $m->setMessage('I am very frustrated');
        $m->setCreatedDate(new \DateTime());
        $m->setSentiment('negative');
        $this->em->persist($m);
        $this->em->flush();

        $this->em->clear();
        $found = $this->em->find(MessageTicket::class, $m->getId());
        $this->assertSame('negative', $found->getSentiment());
    }

    public function testMessageSentimentNullable(): void
    {
        $t = $this->createTicket();
        $m = new MessageTicket();
        $m->setTicket($t);
        $m->setSenderId($t->getUserId());
        $m->setMessage('Normal message');
        $m->setCreatedDate(new \DateTime());
        // no sentiment set
        $this->em->persist($m);
        $this->em->flush();

        $this->em->clear();
        $found = $this->em->find(MessageTicket::class, $m->getId());
        $this->assertNull($found->getSentiment());
    }

    // ── Repository new methods ──

    public function testSearchByUserMethod(): void
    {
        $t = $this->createTicket();
        $repo = $this->em->getRepository(Ticket::class);

        $results = $repo->searchByUser($t->getUserId(), 'Sync');
        $this->assertNotEmpty($results);
        $this->assertSame($t->getId(), $results[0]->getId());
    }

    public function testSearchByUserNoMatch(): void
    {
        $this->createTicket();
        $repo = $this->em->getRepository(Ticket::class);

        $results = $repo->searchByUser(999999, 'nonexistent');
        $this->assertEmpty($results);
    }

    public function testCountByCategory(): void
    {
        $this->createTicket(); // category = TECHNIQUE
        $repo = $this->em->getRepository(Ticket::class);

        $stats = $repo->countByCategory();
        $this->assertIsArray($stats);
        // Should have at least one category
        $this->assertNotEmpty($stats);
    }

    public function testCountLast7DaysVolume(): void
    {
        $this->createTicket();
        $repo = $this->em->getRepository(Ticket::class);

        $stats = $repo->countLast7DaysVolume();
        $this->assertIsArray($stats);
    }

    // ── Ticket workflow with new fields ──

    public function testTicketFullWorkflow(): void
    {
        $t = $this->createTicket();
        $adminUser = $this->createTestUser(['role' => 'ADMIN']);

        // Open → IN_PROGRESS
        $t->setStatus('IN_PROGRESS');
        $t->setAssignedTo($adminUser->getId());
        $this->em->flush();

        // Add message with sentiment
        $m = new MessageTicket();
        $m->setTicket($t);
        $m->setSenderId($t->getUserId());
        $m->setMessage('Still having issues');
        $m->setCreatedDate(new \DateTime());
        $m->setSentiment('frustrated');
        $this->em->persist($m);
        $this->em->flush();

        // Resolve
        $t->setStatus('RESOLVED');
        $t->setResolvedDate(new \DateTime());
        $this->em->flush();

        // Add feedback
        $fb = new Feedback();
        $fb->setTicket($t);
        $fb->setUserId($t->getUserId());
        $fb->setRating(4);
        $fb->setComment('Issue resolved');
        $fb->setFeedbackType('SATISFACTION');
        $fb->setCreatedDate(new \DateTime());
        $this->em->persist($fb);
        $this->em->flush();

        $this->em->clear();

        $found = $this->em->find(Ticket::class, $t->getId());
        $this->assertSame('RESOLVED', $found->getStatus());
        $this->assertNotNull($found->getResolvedDate());
        $this->assertCount(1, $found->getMessages());
        $this->assertSame('frustrated', $found->getMessages()->first()->getSentiment());
        $this->assertCount(1, $found->getFeedbacks());
    }
}

<?php

namespace App\Tests\Integration;

use App\Entity\Feedback;
use App\Entity\MessageTicket;
use App\Entity\Ticket;

class SupportCrudTest extends DatabaseTestCase
{
    private function createTicket(): Ticket
    {
        $user = $this->createTestUser();
        $t = new Ticket();
        $t->setUserId($user->getId());
        $t->setSubject('Test Ticket');
        $t->setCategory('TECHNIQUE');
        $t->setPriority('MEDIUM');
        $t->setDescription('Integration test ticket');
        $t->setStatus('OPEN');
        $t->setCreatedDate(new \DateTime());
        $this->em->persist($t);
        $this->em->flush();

        return $t;
    }

    public function testCreateTicket(): void
    {
        $t = $this->createTicket();
        $this->assertNotNull($t->getId());
        $this->assertSame('OPEN', $t->getStatus());
    }

    public function testReadTicket(): void
    {
        $t = $this->createTicket();
        $id = $t->getId();
        $this->em->clear();

        $found = $this->em->find(Ticket::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('Test Ticket', $found->getSubject());
    }

    public function testUpdateTicketStatus(): void
    {
        $t = $this->createTicket();
        $t->setStatus('IN_PROGRESS');
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(Ticket::class, $t->getId());
        $this->assertSame('IN_PROGRESS', $found->getStatus());
    }

    public function testDeleteTicket(): void
    {
        $t = $this->createTicket();
        $id = $t->getId();
        $this->em->remove($t);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(Ticket::class, $id));
    }

    public function testCreateMessage(): void
    {
        $t = $this->createTicket();
        $m = new MessageTicket();
        $m->setTicket($t);
        $m->setSenderId($t->getUserId());
        $m->setMessage('Need help please');
        $m->setCreatedDate(new \DateTime());
        $this->em->persist($m);
        $this->em->flush();

        $this->assertNotNull($m->getId());
    }

    public function testCreateFeedback(): void
    {
        $t = $this->createTicket();
        $fb = new Feedback();
        $fb->setTicket($t);
        $fb->setUserId($t->getUserId());
        $fb->setRating(5);
        $fb->setComment('Excellent support');
        $fb->setFeedbackType('SATISFACTION');
        $fb->setCreatedDate(new \DateTime());
        $this->em->persist($fb);
        $this->em->flush();

        $this->assertNotNull($fb->getId());
    }

    public function testTicketResolve(): void
    {
        $t = $this->createTicket();
        $t->setStatus('RESOLVED');
        $t->setResolvedDate(new \DateTime());
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(Ticket::class, $t->getId());
        $this->assertSame('RESOLVED', $found->getStatus());
        $this->assertNotNull($found->getResolvedDate());
    }
}

<?php

namespace App\Tests\Integration;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for integration tests that need the Entity Manager.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->em->getConnection()->isTransactionActive()) {
            $this->em->getConnection()->rollBack();
        }
        $this->em->close();
        parent::tearDown();
    }

    protected function createTestUser(array $o = []): User
    {
        $u = new User();
        $u->setUsername($o['username'] ?? 'testuser_' . bin2hex(random_bytes(4)));
        $u->setEmail($o['email'] ?? $u->getUsername() . '@test.com');
        $u->setFullName($o['fullName'] ?? 'Test User');
        $u->setRole($o['role'] ?? 'USER');
        $u->setActive($o['active'] ?? true);
        $u->setPassword($o['password'] ?? 'hashed_pass');
        $this->em->persist($u);
        $this->em->flush();

        return $u;
    }

    protected function refreshEntity(object $entity): object
    {
        $this->em->refresh($entity);

        return $entity;
    }
}

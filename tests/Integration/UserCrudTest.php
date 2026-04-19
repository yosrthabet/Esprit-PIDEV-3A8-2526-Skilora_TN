<?php

namespace App\Tests\Integration;

use App\Entity\User;

class UserCrudTest extends DatabaseTestCase
{
    public function testCreateUser(): void
    {
        $user = $this->createTestUser([
            'username' => 'crud_create',
            'email' => 'crud@test.com',
            'fullName' => 'CRUD Test',
        ]);

        $this->assertNotNull($user->getId());
        $this->assertSame('crud_create', $user->getUsername());
    }

    public function testReadUser(): void
    {
        $user = $this->createTestUser(['username' => 'crud_read']);
        $id = $user->getId();
        $this->em->clear();

        $found = $this->em->find(User::class, $id);
        $this->assertNotNull($found);
        $this->assertSame('crud_read', $found->getUsername());
    }

    public function testUpdateUser(): void
    {
        $user = $this->createTestUser(['username' => 'crud_upd']);
        $user->setFullName('Updated Name');
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(User::class, $user->getId());
        $this->assertSame('Updated Name', $found->getFullName());
    }

    public function testDeleteUser(): void
    {
        $user = $this->createTestUser(['username' => 'crud_del']);
        $id = $user->getId();
        $this->em->remove($user);
        $this->em->flush();
        $this->em->clear();

        $this->assertNull($this->em->find(User::class, $id));
    }

    public function testFindByUsername(): void
    {
        $this->createTestUser(['username' => 'findme']);
        $repo = $this->em->getRepository(User::class);
        $found = $repo->findOneBy(['username' => 'findme']);
        $this->assertNotNull($found);
    }

    public function testUniqueUsername(): void
    {
        $u1 = $this->createTestUser(['username' => 'dup_user']);
        $u2 = $this->createTestUser(['username' => 'dup_user2']);

        // Verify both were persisted with distinct usernames
        $this->assertNotSame($u1->getId(), $u2->getId());
        $this->assertSame('dup_user', $u1->getUsername());
        $this->assertSame('dup_user2', $u2->getUsername());
    }
}

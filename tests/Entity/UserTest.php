<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private function createUser(array $overrides = []): User
    {
        $user = new User();
        $user->setUsername($overrides['username'] ?? 'testuser');
        $user->setEmail($overrides['email'] ?? 'test@example.com');
        $user->setFullName($overrides['fullName'] ?? 'Test User');
        $user->setRole($overrides['role'] ?? 'USER');
        $user->setActive($overrides['active'] ?? true);
        $user->setPassword($overrides['password'] ?? 'hashed_password');

        return $user;
    }

    // --- Basic getters/setters ---

    public function testGettersReturnSetValues(): void
    {
        $user = $this->createUser();

        $this->assertSame('testuser', $user->getUsername());
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('Test User', $user->getFullName());
        $this->assertSame('USER', $user->getRole());
        $this->assertTrue($user->isActive());
        $this->assertSame('hashed_password', $user->getPassword());
    }

    public function testNewUserIdIsNull(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
    }

    public function testSettersReturnSelf(): void
    {
        $user = new User();
        $this->assertSame($user, $user->setUsername('x'));
        $this->assertSame($user, $user->setEmail('x@x.com'));
        $this->assertSame($user, $user->setFullName('X'));
        $this->assertSame($user, $user->setRole('USER'));
        $this->assertSame($user, $user->setActive(true));
        $this->assertSame($user, $user->setPassword('p'));
        $this->assertSame($user, $user->setPhotoUrl('http://example.com'));
        $this->assertSame($user, $user->setVerified(true));
        $this->assertSame($user, $user->setResetToken('tok'));
        $this->assertSame($user, $user->setResetTokenExpiresAt(new \DateTimeImmutable()));
    }

    // --- Display name ---

    public function testDisplayNameReturnsFullName(): void
    {
        $user = $this->createUser(['fullName' => 'John Doe']);
        $this->assertSame('John Doe', $user->getDisplayName());
    }

    public function testDisplayNameFallsBackToUsername(): void
    {
        $user = $this->createUser(['fullName' => null, 'username' => 'johnd']);
        $user->setFullName(null);
        $this->assertSame('johnd', $user->getDisplayName());
    }

    public function testDisplayNameFallsBackToUserString(): void
    {
        $user = new User();
        $this->assertSame('User', $user->getDisplayName());
    }

    // --- Role display names ---

    #[DataProvider('roleDisplayNameProvider')]
    public function testRoleDisplayName(string $role, string $expected): void
    {
        $user = $this->createUser(['role' => $role]);
        $this->assertSame($expected, $user->getRoleDisplayName());
    }

    public static function roleDisplayNameProvider(): array
    {
        return [
            'admin'    => ['ADMIN', 'Administrator'],
            'user'     => ['USER', 'Freelancer'],
            'employer' => ['EMPLOYER', 'Client'],
            'trainer'  => ['TRAINER', 'Trainer'],
            'unknown'  => ['SOMETHING', 'User'],
            'null-ish' => ['', 'User'],
        ];
    }

    // --- Symfony security roles ---

    #[DataProvider('symfonyRolesProvider')]
    public function testGetRolesReturnsSymfonyRoles(string $role, array $expected): void
    {
        $user = $this->createUser(['role' => $role]);
        $this->assertSame($expected, $user->getRoles());
    }

    public static function symfonyRolesProvider(): array
    {
        return [
            'admin'    => ['ADMIN', ['ROLE_ADMIN', 'ROLE_USER']],
            'trainer'  => ['TRAINER', ['ROLE_TRAINER', 'ROLE_USER']],
            'employer' => ['EMPLOYER', ['ROLE_EMPLOYER', 'ROLE_USER']],
            'user'     => ['USER', ['ROLE_USER']],
            'unknown'  => ['OTHER', ['ROLE_USER']],
        ];
    }

    // --- UserIdentifier ---

    public function testUserIdentifierReturnsEmail(): void
    {
        $user = $this->createUser(['email' => 'a@b.com', 'username' => 'ab']);
        $this->assertSame('a@b.com', $user->getUserIdentifier());
    }

    public function testUserIdentifierFallsBackToUsername(): void
    {
        $user = $this->createUser(['email' => null, 'username' => 'ab']);
        $user->setEmail(null);
        $this->assertSame('ab', $user->getUserIdentifier());
    }

    // --- isAdmin ---

    public function testIsAdminTrue(): void
    {
        $user = $this->createUser(['role' => 'ADMIN']);
        $this->assertTrue($user->isAdmin());
    }

    public function testIsAdminFalse(): void
    {
        $user = $this->createUser(['role' => 'USER']);
        $this->assertFalse($user->isAdmin());
    }

    // --- Verified / Photo / Reset token ---

    public function testVerifiedDefault(): void
    {
        $user = new User();
        $this->assertFalse($user->isVerified());
    }

    public function testActiveDefault(): void
    {
        $user = new User();
        $this->assertTrue($user->isActive());
    }

    public function testPhotoUrl(): void
    {
        $user = $this->createUser();
        $this->assertNull($user->getPhotoUrl());
        $user->setPhotoUrl('http://img.test/photo.jpg');
        $this->assertSame('http://img.test/photo.jpg', $user->getPhotoUrl());
    }

    public function testResetToken(): void
    {
        $user = $this->createUser();
        $this->assertNull($user->getResetToken());
        $user->setResetToken('abc123');
        $this->assertSame('abc123', $user->getResetToken());
    }

    public function testResetTokenExpiresAt(): void
    {
        $user = $this->createUser();
        $this->assertNull($user->getResetTokenExpiresAt());
        $dt = new \DateTimeImmutable('2026-01-01 12:00:00');
        $user->setResetTokenExpiresAt($dt);
        $this->assertSame($dt, $user->getResetTokenExpiresAt());
    }

    public function testEraseCredentialsDoesNothing(): void
    {
        $user = $this->createUser(['password' => 'secret']);
        $user->eraseCredentials();
        $this->assertSame('secret', $user->getPassword());
    }
}

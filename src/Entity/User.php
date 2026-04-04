<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Table name in DB is `users`. If your table is singular `user`, change `name:` below to `user`.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(
    name: 'users',
    indexes: [new ORM\Index(name: 'idx_users_email', columns: ['email'])],
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'username', columns: ['username'])],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $username = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = null;

    /** Hashed password (e.g. bcrypt) — column name remains "password" in MySQL */
    #[ORM\Column(name: 'password', length: 255)]
    private string $passwordHash = '';

    #[ORM\Column(length: 20)]
    private string $role = '';

    #[ORM\Column(name: 'full_name', length: 100)]
    private string $fullName = '';

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE,
        columnDefinition: 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
    )]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'photo_url', type: Types::TEXT, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(name: 'is_verified', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isVerified = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isActive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;

        return $this;
    }

    public function isVerified(): bool
    {
        return (bool) $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive ?? true;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Used after login for the session (must be non-empty). Prefer email when set, else username.
     */
    public function getUserIdentifier(): string
    {
        $email = $this->email;
        if (null !== $email && '' !== trim($email)) {
            return trim($email);
        }

        return $this->username;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $r = strtoupper(trim($this->role));

        if ('ADMIN' === $r) {
            return ['ROLE_ADMIN', 'ROLE_USER'];
        }

        return ['ROLE_USER'];
    }

    /** Hash stored in DB column `password` (see property $passwordHash). */
    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
    }
}


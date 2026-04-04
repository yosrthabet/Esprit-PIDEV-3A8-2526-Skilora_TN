<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Maps to the existing `users` table (legacy schema). Login uses `email`.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $username;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private string $password;

    /** Legacy single role (e.g. USER, ADMIN, EMPLOYER). */
    #[ORM\Column(length: 20)]
    private string $role = 'USER';

    #[ORM\Column(name: 'full_name', length: 100)]
    private string $fullName = '';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'photo_url', type: Types::TEXT, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(name: 'is_verified', options: ['default' => 0])]
    private bool $isVerified = false;

    #[ORM\Column(name: 'is_active', options: ['default' => 1])]
    private bool $isActive = true;

    #[ORM\Column(name: 'two_factor_enabled', options: ['default' => 0])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(name: 'two_factor_enabled_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $twoFactorEnabledAt = null;

    #[ORM\Column(name: 'two_factor_locked_until', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $twoFactorLockedUntil = null;

    #[ORM\Column(name: 'terms_accepted', options: ['default' => 0])]
    private bool $termsAccepted = false;

    #[ORM\Column(name: 'terms_accepted_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $termsAcceptedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $r = strtoupper($this->role);

        return match ($r) {
            'ADMIN' => ['ROLE_ADMIN', 'ROLE_USER'],
            'EMPLOYER' => ['ROLE_EMPLOYER', 'ROLE_USER'],
            'TRAINER' => ['ROLE_TRAINER', 'ROLE_USER'],
            default => ['ROLE_USER'],
        };
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getLegacyRole(): string
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

    public function eraseCredentials(): void
    {
    }
}

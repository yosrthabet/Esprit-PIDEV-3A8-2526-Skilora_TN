<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(name: 'full_name', length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(name: 'photo_url', type: 'text', nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(name: 'is_verified', type: 'boolean', options: ['default' => false])]
    private bool $verified = false;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'two_factor_enabled', type: 'boolean', options: ['default' => false])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(name: 'two_factor_enabled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $twoFactorEnabledAt = null;

    #[ORM\Column(name: 'two_factor_locked_until', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $twoFactorLockedUntil = null;

    #[ORM\Column(name: 'terms_accepted', type: 'boolean', options: ['default' => false])]
    private bool $termsAccepted = false;

    #[ORM\Column(name: 'terms_accepted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $termsAcceptedAt = null;

    #[ORM\Column(name: 'reset_token', length: 64, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_token_expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    /** @var Collection<int, CommunityPost> */
    #[ORM\OneToMany(targetEntity: CommunityPost::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $communityPosts;

    public function __construct()
    {
        $this->communityPosts = new ArrayCollection();
    }

    /**
     * @return Collection<int, CommunityPost>
     */
    public function getCommunityPosts(): Collection
    {
        return $this->communityPosts;
    }

    public function addCommunityPost(CommunityPost $post): self
    {
        if (!$this->communityPosts->contains($post)) {
            $this->communityPosts->add($post);
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removeCommunityPost(CommunityPost $post): self
    {
        $this->communityPosts->removeElement($post);

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;
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
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    // --- Symfony Security Interface ---

    public function getUserIdentifier(): string
    {
        return $this->email ?? $this->username;
    }

    public function getRoles(): array
    {
        // Map JavaFX role string to Symfony role
        $roles = ['ROLE_USER'];

        return match (strtoupper($this->role ?? '')) {
            'ADMIN' => ['ROLE_ADMIN', 'ROLE_USER'],
            'TRAINER' => ['ROLE_TRAINER', 'ROLE_USER'],
            'EMPLOYER' => ['ROLE_EMPLOYER', 'ROLE_USER'],
            default => $roles,
        };
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase — password stays hashed in DB
    }

    // --- Helpers ---

    public function getDisplayName(): string
    {
        return $this->fullName ?? $this->username ?? 'User';
    }

    public function getRoleDisplayName(): string
    {
        return match (strtoupper($this->role ?? '')) {
            'ADMIN' => 'Administrator',
            'USER' => 'Freelancer',
            'EMPLOYER' => 'Client',
            'TRAINER' => 'Trainer',
            default => 'User',
        };
    }

    public function isAdmin(): bool
    {
        return strtoupper($this->role ?? '') === 'ADMIN';
    }
}

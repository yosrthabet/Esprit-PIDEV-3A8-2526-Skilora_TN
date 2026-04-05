<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $username = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private string $password = '';

    #[ORM\Column(length: 20)]
    private string $role = 'USER';

    #[ORM\Column(length: 100)]
    private string $full_name = '';

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTime $created_at;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $photo_url = null;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $is_verified = false;

    #[ORM\Column(type: 'boolean', options: ['default' => 1])]
    private bool $is_active = true;

    /** @var Collection<int, CommunityPost> */
    #[ORM\OneToMany(targetEntity: CommunityPost::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $communityPosts;

    public function __construct()
    {
        $this->created_at = new \DateTime();
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

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->full_name;
    }

    public function setFullName(string $full_name): self
    {
        $this->full_name = $full_name;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photo_url;
    }

    public function setPhotoUrl(?string $photo_url): self
    {
        $this->photo_url = $photo_url;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(bool $is_verified): self
    {
        $this->is_verified = $is_verified;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getRoles(): array
    {
        // Tout utilisateur connecté a au moins ROLE_USER (requis par IsGranted, chemins protégés, etc.)
        $roles = ['ROLE_' . strtoupper($this->role), 'ROLE_USER'];

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}

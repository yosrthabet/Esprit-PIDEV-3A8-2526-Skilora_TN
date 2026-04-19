<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'passkey_credentials')]
class PasskeyCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** Base64url-encoded credential ID */
    #[ORM\Column(name: 'credential_id', type: 'text')]
    private string $credentialId;

    /** Base64url-encoded public key (COSE) */
    #[ORM\Column(name: 'public_key', type: 'text')]
    private string $publicKey;

    /** Signature counter for replay detection */
    #[ORM\Column(name: 'sign_count', type: 'integer', options: ['default' => 0])]
    private int $signCount = 0;

    /** Human-readable label (e.g. "MacBook Touch ID") */
    #[ORM\Column(length: 255)]
    private string $name;

    /** Attestation type (e.g. "none", "packed") */
    #[ORM\Column(name: 'attestation_type', length: 32, options: ['default' => 'none'])]
    private string $attestationType = 'none';

    /** Transports supported by the authenticator (json array) */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $transports = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_used_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getCredentialId(): string { return $this->credentialId; }
    public function setCredentialId(string $credentialId): static { $this->credentialId = $credentialId; return $this; }

    public function getPublicKey(): string { return $this->publicKey; }
    public function setPublicKey(string $publicKey): static { $this->publicKey = $publicKey; return $this; }

    public function getSignCount(): int { return $this->signCount; }
    public function setSignCount(int $signCount): static { $this->signCount = $signCount; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAttestationType(): string { return $this->attestationType; }
    public function setAttestationType(string $attestationType): static { $this->attestationType = $attestationType; return $this; }

    public function getTransports(): ?array { return $this->transports; }
    public function setTransports(?array $transports): static { $this->transports = $transports; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getLastUsedAt(): ?\DateTimeImmutable { return $this->lastUsedAt; }
    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): static { $this->lastUsedAt = $lastUsedAt; return $this; }
}

<?php

namespace App\Entity;

use App\Repository\CommunityNotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityNotificationRepository::class)]
#[ORM\Table(name: 'community_notifications')]
class CommunityNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private string $type = 'INFO';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $icon = '🔔';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $referenceType = null;

    #[ORM\Column(nullable: true)]
    private ?int $referenceId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function getIcon(): ?string { return $this->icon; }
    public function setIcon(?string $icon): self { $this->icon = $icon; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): self { $this->isRead = $isRead; return $this; }

    public function getReferenceType(): ?string { return $this->referenceType; }
    public function setReferenceType(?string $referenceType): self { $this->referenceType = $referenceType; return $this; }

    public function getReferenceId(): ?int { return $this->referenceId; }
    public function setReferenceId(?int $referenceId): self { $this->referenceId = $referenceId; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

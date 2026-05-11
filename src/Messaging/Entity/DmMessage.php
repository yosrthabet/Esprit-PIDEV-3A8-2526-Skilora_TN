<?php

declare(strict_types=1);

namespace App\Messaging\Entity;

use App\Entity\User;
use App\Messaging\Repository\DmMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DmMessageRepository::class)]
#[ORM\Table(name: 'dm_messages')]
#[ORM\Index(columns: ['conversation_id', 'id'], name: 'idx_dm_message_conversation_id')]
#[ORM\HasLifecycleCallbacks]
class DmMessage
{
    public const TYPE_TEXT = 'text';
    public const TYPE_VOICE = 'voice';
    public const TYPE_IMAGE = 'image';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DmConversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DmConversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(type: Types::TEXT)]
    private string $body = '';

    #[ORM\Column(length: 10, options: ['default' => 'text'])]
    private string $messageType = self::TYPE_TEXT;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $voiceUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'read_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getConversation(): DmConversation { return $this->conversation; }
    public function setConversation(DmConversation $conversation): static { $this->conversation = $conversation; return $this; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $sender): static { $this->sender = $sender; return $this; }
    public function getBody(): string { return $this->body; }
    public function setBody(string $body): static { $this->body = trim($body); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getMessageType(): string { return $this->messageType; }
    public function setMessageType(string $type): static { $this->messageType = $type; return $this; }
    public function isVoice(): bool { return $this->messageType === self::TYPE_VOICE; }
    public function isImage(): bool { return $this->messageType === self::TYPE_IMAGE; }

    public function getVoiceUrl(): ?string { return $this->voiceUrl; }
    public function setVoiceUrl(?string $url): static { $this->voiceUrl = $url; return $this; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $url): static { $this->imageUrl = $url; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function getReadAt(): ?\DateTimeImmutable { return $this->readAt; }
    public function markAsRead(): static { $this->isRead = true; $this->readAt = new \DateTimeImmutable(); return $this; }
}

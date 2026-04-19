<?php

namespace App\Entity;

use App\Repository\DmMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DmMessageRepository::class)]
#[ORM\Table(name: 'dm_messages')]
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
    private ?DmConversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le message ne peut pas être vide.', groups: ['text'])]
    #[Assert\Length(min: 1, max: 4000, maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères.', groups: ['text'])]
    private string $body = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(length: 10, options: ['default' => 'text'])]
    private string $messageType = self::TYPE_TEXT;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $voiceUrl = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $imageUrl = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?DmConversation
    {
        return $this->conversation;
    }

    public function setConversation(?DmConversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = trim($body);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): self
    {
        $this->readAt = $readAt;

        return $this;
    }

    public function markAsRead(): self
    {
        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();

        return $this;
    }

    public function getMessageType(): string
    {
        return $this->messageType;
    }

    public function setMessageType(string $messageType): self
    {
        $this->messageType = $messageType;

        return $this;
    }

    public function isVoice(): bool
    {
        return $this->messageType === self::TYPE_VOICE;
    }

    public function getVoiceUrl(): ?string
    {
        return $this->voiceUrl;
    }

    public function setVoiceUrl(?string $voiceUrl): self
    {
        $this->voiceUrl = $voiceUrl;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function isImage(): bool
    {
        return $this->messageType === self::TYPE_IMAGE;
    }
}

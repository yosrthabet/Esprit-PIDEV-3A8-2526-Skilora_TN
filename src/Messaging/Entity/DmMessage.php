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
class DmMessage
{
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

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getConversation(): DmConversation { return $this->conversation; }
    public function setConversation(DmConversation $conversation): static { $this->conversation = $conversation; return $this; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $sender): static { $this->sender = $sender; return $this; }
    public function getBody(): string { return $this->body; }
    public function setBody(string $body): static { $this->body = trim($body); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

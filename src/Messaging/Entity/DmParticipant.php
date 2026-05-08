<?php

declare(strict_types=1);

namespace App\Messaging\Entity;

use App\Entity\User;
use App\Messaging\Repository\DmParticipantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DmParticipantRepository::class)]
#[ORM\Table(name: 'dm_participants')]
#[ORM\UniqueConstraint(name: 'uniq_dm_participant_user', columns: ['conversation_id', 'user_id'])]
class DmParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DmConversation::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DmConversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'unread_count')]
    private int $unreadCount = 0;

    #[ORM\Column(name: 'last_read_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastReadAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getConversation(): DmConversation { return $this->conversation; }
    public function setConversation(DmConversation $conversation): static { $this->conversation = $conversation; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getUnreadCount(): int { return $this->unreadCount; }
    public function incrementUnread(): void { $this->unreadCount++; }
    public function markRead(?\DateTimeImmutable $at = null): void { $this->unreadCount = 0; $this->lastReadAt = $at ?? new \DateTimeImmutable(); }
    public function getLastReadAt(): ?\DateTimeImmutable { return $this->lastReadAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

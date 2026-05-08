<?php

declare(strict_types=1);

namespace App\Messaging\Entity;

use App\Entity\User;
use App\Messaging\Repository\DmConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DmConversationRepository::class)]
#[ORM\Table(name: 'dm_conversations')]
#[ORM\Index(columns: ['last_message_at'], name: 'idx_dm_conversation_last_message')]
class DmConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'last_message_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    /** @var Collection<int, DmParticipant> */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: DmParticipant::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $participants;

    /** @var Collection<int, DmMessage> */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: DmMessage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getSubject(): ?string { return $this->subject; }
    public function setSubject(?string $subject): static { $this->subject = $subject !== null ? trim($subject) : null; $this->touch(); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getLastMessageAt(): ?\DateTimeImmutable { return $this->lastMessageAt; }
    public function markMessageActivity(?\DateTimeImmutable $at = null): void { $this->lastMessageAt = $at ?? new \DateTimeImmutable(); $this->touch(); }
    /** @return Collection<int, DmParticipant> */
    public function getParticipants(): Collection { return $this->participants; }
    public function addParticipant(User $user): DmParticipant { $participant = (new DmParticipant())->setConversation($this)->setUser($user); $this->participants->add($participant); return $participant; }
    /** @return Collection<int, DmMessage> */
    public function getMessages(): Collection { return $this->messages; }
    public function addMessage(DmMessage $message): static { if (!$this->messages->contains($message)) { $this->messages->add($message); $message->setConversation($this); } return $this; }

    public function otherParticipant(User $user): ?User
    {
        foreach ($this->participants as $participant) {
            $participantUser = $participant->getUser();
            if ($participantUser !== $user && ($participantUser->getId() === null || $participantUser->getId() !== $user->getId())) {
                return $participant->getUser();
            }
        }

        return null;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

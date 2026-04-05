<?php

namespace App\Entity;

use App\Repository\DmConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DmConversationRepository::class)]
#[ORM\Table(name: 'dm_conversations')]
#[ORM\UniqueConstraint(name: 'uniq_dm_participants', columns: ['participant_low_id', 'participant_high_id'])]
class DmConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'participant_low_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $participantLow = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'participant_high_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $participantHigh = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, DmMessage> */
    #[ORM\OneToMany(targetEntity: DmMessage::class, mappedBy: 'conversation', orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    public static function forUsers(User $a, User $b): self
    {
        $c = new self();
        if ($a->getId() <= $b->getId()) {
            $c->participantLow = $a;
            $c->participantHigh = $b;
        } else {
            $c->participantLow = $b;
            $c->participantHigh = $a;
        }

        return $c;
    }

    public function involves(User $user): bool
    {
        return $this->participantLow->getId() === $user->getId()
            || $this->participantHigh->getId() === $user->getId();
    }

    public function otherParticipant(User $me): User
    {
        if ($this->participantLow->getId() === $me->getId()) {
            return $this->participantHigh;
        }

        return $this->participantLow;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipantLow(): ?User
    {
        return $this->participantLow;
    }

    public function getParticipantHigh(): ?User
    {
        return $this->participantHigh;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, DmMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(DmMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }
}

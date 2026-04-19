<?php

namespace App\Entity;

use App\Repository\EventRsvpRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRsvpRepository::class)]
#[ORM\Table(name: 'event_rsvps')]
#[ORM\UniqueConstraint(name: 'unique_event_user_rsvp', columns: ['event_id', 'user_id'])]
class EventRsvp
{
    public const STATUS_GOING = 'GOING';
    public const STATUS_MAYBE = 'MAYBE';
    public const STATUS_NOT_GOING = 'NOT_GOING';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityEvent::class, inversedBy: 'rsvps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CommunityEvent $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_GOING;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $rsvpDate;

    public function __construct()
    {
        $this->rsvpDate = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEvent(): ?CommunityEvent { return $this->event; }
    public function setEvent(?CommunityEvent $event): self { $this->event = $event; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getRsvpDate(): \DateTimeImmutable { return $this->rsvpDate; }
}

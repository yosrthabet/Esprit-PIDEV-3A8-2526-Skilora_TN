<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\Repository\EventRsvpRepository;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRsvpRepository::class)]
#[ORM\Table(name: 'community_spaces_event_rsvps')]
#[ORM\UniqueConstraint(name: 'uniq_community_event_rsvp', columns: ['event_id', 'user_id'])]
class EventRsvp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityEvent::class, inversedBy: 'rsvps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CommunityEvent $event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEvent(): CommunityEvent { return $this->event; }
    public function setEvent(CommunityEvent $event): static { $this->event = $event; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

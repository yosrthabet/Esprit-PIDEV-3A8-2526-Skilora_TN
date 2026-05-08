<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\Repository\CommunityEventRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityEventRepository::class)]
#[ORM\Table(name: 'community_spaces_events')]
#[ORM\Index(columns: ['starts_at'], name: 'idx_community_event_starts')]
class CommunityEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $host;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'starts_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'online_url', length: 2048, nullable: true)]
    private ?string $onlineUrl = null;

    #[ORM\Column(name: 'rsvps_count')]
    private int $rsvpsCount = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, EventRsvp> */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventRsvp::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $rsvps;

    public function __construct()
    {
        $this->startsAt = new \DateTimeImmutable('+1 week');
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->rsvps = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getHost(): User { return $this->host; }
    public function setHost(User $host): static { $this->host = $host; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = trim($title); $this->touch(); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description !== null ? trim($description) : null; $this->touch(); return $this; }
    public function getStartsAt(): \DateTimeImmutable { return $this->startsAt; }
    public function setStartsAt(\DateTimeImmutable $startsAt): static { $this->startsAt = $startsAt; $this->touch(); return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location !== null ? trim($location) : null; $this->touch(); return $this; }
    public function getOnlineUrl(): ?string { return $this->onlineUrl; }
    public function setOnlineUrl(?string $onlineUrl): static { $this->onlineUrl = $onlineUrl !== null ? trim($onlineUrl) : null; $this->touch(); return $this; }
    public function getRsvpsCount(): int { return $this->rsvpsCount; }
    public function incrementRsvps(): void { $this->rsvpsCount++; $this->touch(); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    /** @return Collection<int, EventRsvp> */
    public function getRsvps(): Collection { return $this->rsvps; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

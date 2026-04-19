<?php

namespace App\Entity;

use App\Repository\CommunityEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommunityEventRepository::class)]
#[ORM\Table(name: 'community_events')]
#[ORM\HasLifecycleCallbacks]
class CommunityEvent
{
    public const TYPE_MEETUP = 'MEETUP';
    public const TYPE_WEBINAR = 'WEBINAR';
    public const TYPE_WORKSHOP = 'WORKSHOP';
    public const TYPE_CONFERENCE = 'CONFERENCE';
    public const TYPE_NETWORKING = 'NETWORKING';

    public const STATUS_UPCOMING = 'UPCOMING';
    public const STATUS_ONGOING = 'ONGOING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const TYPES = [
        'Rencontre' => self::TYPE_MEETUP,
        'Webinaire' => self::TYPE_WEBINAR,
        'Atelier' => self::TYPE_WORKSHOP,
        'Conférence' => self::TYPE_CONFERENCE,
        'Réseautage' => self::TYPE_NETWORKING,
    ];

    public const STATUSES = [
        'À venir' => self::STATUS_UPCOMING,
        'En cours' => self::STATUS_ONGOING,
        'Terminé' => self::STATUS_COMPLETED,
        'Annulé' => self::STATUS_CANCELLED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $organizer = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est requis.')]
    #[Assert\Length(min: 3, max: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    #[ORM\Column(length: 30)]
    private string $eventType = self::TYPE_MEETUP;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isOnline = false;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url(protocols: ['http', 'https'])]
    private ?string $onlineLink = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date de début est requise.')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $maxAttendees = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $currentAttendees = 0;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url(protocols: ['http', 'https'])]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_UPCOMING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, EventRsvp> */
    #[ORM\OneToMany(targetEntity: EventRsvp::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $rsvps;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->rsvps = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getOrganizer(): ?User { return $this->organizer; }
    public function setOrganizer(?User $organizer): self { $this->organizer = $organizer; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = trim($title); return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getEventType(): string { return $this->eventType; }
    public function setEventType(string $eventType): self { $this->eventType = $eventType; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): self { $this->location = $location; return $this; }

    public function isOnline(): bool { return $this->isOnline; }
    public function setIsOnline(bool $isOnline): self { $this->isOnline = $isOnline; return $this; }

    public function getOnlineLink(): ?string { return $this->onlineLink; }
    public function setOnlineLink(?string $onlineLink): self {
        $t = $onlineLink ? trim($onlineLink) : '';
        $this->onlineLink = $t === '' ? null : $t;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable { return $this->startDate; }
    public function setStartDate(?\DateTimeImmutable $startDate): self { $this->startDate = $startDate; return $this; }

    public function getEndDate(): ?\DateTimeImmutable { return $this->endDate; }
    public function setEndDate(?\DateTimeImmutable $endDate): self { $this->endDate = $endDate; return $this; }

    public function getMaxAttendees(): int { return $this->maxAttendees; }
    public function setMaxAttendees(int $max): self { $this->maxAttendees = $max; return $this; }

    public function getCurrentAttendees(): int { return $this->currentAttendees; }
    public function setCurrentAttendees(int $count): self { $this->currentAttendees = $count; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $url): self { $t = $url ? trim($url) : ''; $this->imageUrl = $t === '' ? null : $t; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, EventRsvp> */
    public function getRsvps(): Collection { return $this->rsvps; }

    public function isFull(): bool
    {
        return $this->maxAttendees > 0 && $this->currentAttendees >= $this->maxAttendees;
    }

    public function getEventTypeLabel(): string
    {
        return array_search($this->eventType, self::TYPES, true) ?: $this->eventType;
    }

    public function getStatusLabel(): string
    {
        return array_search($this->status, self::STATUSES, true) ?: $this->status;
    }
}

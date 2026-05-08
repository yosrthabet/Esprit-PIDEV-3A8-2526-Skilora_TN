<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Entity\User;
use App\Formation\FormationLevel;
use App\Formation\FormationStatus;
use App\Formation\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formations')]
#[ORM\Index(columns: ['status', 'category'], name: 'idx_formation_status_category')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $trainer;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 120)]
    private string $category = '';

    #[ORM\Column(length: 20, enumType: FormationLevel::class)]
    private FormationLevel $level = FormationLevel::BEGINNER;

    #[ORM\Column(name: 'duration_hours')]
    private int $durationHours = 0;

    #[ORM\Column(name: 'price_amount', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $priceAmount = null;

    #[ORM\Column(length: 20, enumType: FormationStatus::class)]
    private FormationStatus $status = FormationStatus::DRAFT;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, FormationModule> */
    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: FormationModule::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $modules;

    /** @var Collection<int, Enrollment> */
    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Enrollment::class)]
    private Collection $enrollments;

    /** @var Collection<int, FormationReview> */
    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: FormationReview::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $reviews;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->modules = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTrainer(): User { return $this->trainer; }
    public function setTrainer(User $trainer): static { $this->trainer = $trainer; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; $this->touch(); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; $this->touch(); return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; $this->touch(); return $this; }
    public function getLevel(): FormationLevel { return $this->level; }
    public function setLevel(FormationLevel $level): static { $this->level = $level; $this->touch(); return $this; }
    public function getDurationHours(): int { return $this->durationHours; }
    public function setDurationHours(int $durationHours): static { $this->durationHours = max(0, $durationHours); $this->touch(); return $this; }
    public function getPriceAmount(): ?string { return $this->priceAmount; }
    public function setPriceAmount(?string $priceAmount): static { $this->priceAmount = $priceAmount; $this->touch(); return $this; }
    public function getStatus(): FormationStatus { return $this->status; }
    public function setStatus(FormationStatus $status): static { $this->status = $status; $this->touch(); return $this; }
    public function publish(): static { return $this->setStatus(FormationStatus::PUBLISHED); }
    public function archive(): static { return $this->setStatus(FormationStatus::ARCHIVED); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    /** @return Collection<int, FormationModule> */
    public function getModules(): Collection { return $this->modules; }
    public function addModule(FormationModule $module): static { if (!$this->modules->contains($module)) { $this->modules->add($module); $module->setFormation($this); } return $this; }
    public function removeModule(FormationModule $module): static { $this->modules->removeElement($module); return $this; }
    /** @return Collection<int, Enrollment> */
    public function getEnrollments(): Collection { return $this->enrollments; }
    /** @return Collection<int, FormationReview> */
    public function getReviews(): Collection { return $this->reviews; }
    public function isPublished(): bool { return $this->status === FormationStatus::PUBLISHED; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Formation\Repository\FormationModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationModuleRepository::class)]
#[ORM\Table(name: 'formation_modules')]
#[ORM\UniqueConstraint(name: 'uniq_formation_module_position', columns: ['formation_id', 'position'])]
class FormationModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'modules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Formation $formation;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(name: 'duration_minutes')]
    private int $durationMinutes = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, FormationMaterial> */
    #[ORM\OneToMany(mappedBy: 'module', targetEntity: FormationMaterial::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $materials;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->materials = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getFormation(): Formation { return $this->formation; }
    public function setFormation(Formation $formation): static { $this->formation = $formation; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = max(0, $position); return $this; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function setDurationMinutes(int $durationMinutes): static { $this->durationMinutes = max(0, $durationMinutes); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    /** @return Collection<int, FormationMaterial> */
    public function getMaterials(): Collection { return $this->materials; }
    public function addMaterial(FormationMaterial $material): static { if (!$this->materials->contains($material)) { $this->materials->add($material); $material->setModule($this); } return $this; }
}

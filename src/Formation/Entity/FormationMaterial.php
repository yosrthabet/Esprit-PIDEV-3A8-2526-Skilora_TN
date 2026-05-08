<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Formation\Repository\FormationMaterialRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationMaterialRepository::class)]
#[ORM\Table(name: 'formation_materials')]
#[ORM\Index(columns: ['module_id', 'position'], name: 'idx_formation_material_module_position')]
class FormationMaterial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FormationModule::class, inversedBy: 'materials')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FormationModule $module;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(name: 'resource_url', length: 2048)]
    private string $resourceUrl = '';

    #[ORM\Column(length: 40)]
    private string $kind = 'link';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getModule(): FormationModule { return $this->module; }
    public function setModule(FormationModule $module): static { $this->module = $module; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getResourceUrl(): string { return $this->resourceUrl; }
    public function setResourceUrl(string $resourceUrl): static { $this->resourceUrl = $resourceUrl; return $this; }
    public function getKind(): string { return $this->kind; }
    public function setKind(string $kind): static { $this->kind = $kind !== '' ? $kind : 'link'; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = max(0, $position); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

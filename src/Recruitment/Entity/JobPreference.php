<?php

declare(strict_types=1);

namespace App\Recruitment\Entity;

use App\Entity\User;
use App\Enum\WorkType;
use App\Recruitment\Repository\JobPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobPreferenceRepository::class)]
#[ORM\Table(name: 'job_preferences')]
class JobPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $targetTitle = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 20, nullable: true, enumType: WorkType::class)]
    private ?WorkType $workType = null;

    #[ORM\Column(name: 'min_salary', nullable: true)]
    private ?int $minSalary = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getTargetTitle(): ?string { return $this->targetTitle; }
    public function setTargetTitle(?string $targetTitle): static { $this->targetTitle = $targetTitle; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }
    public function getWorkType(): ?WorkType { return $this->workType; }
    public function setWorkType(?WorkType $workType): static { $this->workType = $workType; return $this; }
    public function getMinSalary(): ?int { return $this->minSalary; }
    public function setMinSalary(?int $minSalary): static { $this->minSalary = $minSalary; return $this; }
}

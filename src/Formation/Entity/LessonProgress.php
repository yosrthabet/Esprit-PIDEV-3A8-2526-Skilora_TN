<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Formation\Repository\LessonProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonProgressRepository::class)]
#[ORM\Table(name: 'formation_lesson_progress')]
#[ORM\UniqueConstraint(name: 'uniq_lesson_progress_enrollment_module', columns: ['enrollment_id', 'module_id'])]
class LessonProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Enrollment::class, inversedBy: 'lessonProgress')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Enrollment $enrollment;

    #[ORM\ManyToOne(targetEntity: FormationModule::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FormationModule $module;

    #[ORM\Column(name: 'progress_percent')]
    private int $progressPercent = 0;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEnrollment(): Enrollment { return $this->enrollment; }
    public function setEnrollment(Enrollment $enrollment): static { $this->enrollment = $enrollment; return $this; }
    public function getModule(): FormationModule { return $this->module; }
    public function setModule(FormationModule $module): static { $this->module = $module; return $this; }
    public function getProgressPercent(): int { return $this->progressPercent; }
    public function setProgressPercent(int $progressPercent): static { $this->progressPercent = max(0, min(100, $progressPercent)); $this->completedAt = $this->progressPercent === 100 ? new \DateTimeImmutable() : null; $this->touch(); return $this; }
    public function markComplete(?\DateTimeImmutable $at = null): static { $this->progressPercent = 100; $this->completedAt = $at ?? new \DateTimeImmutable(); $this->touch(); return $this; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function isComplete(): bool { return $this->progressPercent === 100; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

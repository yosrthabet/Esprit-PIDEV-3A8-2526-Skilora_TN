<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Entity\User;
use App\Formation\EnrollmentStatus;
use App\Formation\Repository\EnrollmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'formation_enrollments')]
#[ORM\UniqueConstraint(name: 'uniq_formation_enrollment_user', columns: ['formation_id', 'user_id'])]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Formation $formation;

    #[ORM\Column(length: 20, enumType: EnrollmentStatus::class)]
    private EnrollmentStatus $status = EnrollmentStatus::ACTIVE;

    #[ORM\Column(name: 'enrolled_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $enrolledAt;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /** @var Collection<int, LessonProgress> */
    #[ORM\OneToMany(mappedBy: 'enrollment', targetEntity: LessonProgress::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $lessonProgress;

    #[ORM\OneToOne(mappedBy: 'enrollment', targetEntity: Certificate::class, cascade: ['persist'], orphanRemoval: true)]
    private ?Certificate $certificate = null;

    public function __construct()
    {
        $this->enrolledAt = new \DateTimeImmutable();
        $this->lessonProgress = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getFormation(): Formation { return $this->formation; }
    public function setFormation(Formation $formation): static { $this->formation = $formation; return $this; }
    public function getStatus(): EnrollmentStatus { return $this->status; }
    public function setStatus(EnrollmentStatus $status): static { $this->status = $status; return $this; }
    public function getEnrolledAt(): \DateTimeImmutable { return $this->enrolledAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function complete(?\DateTimeImmutable $at = null): static { $this->status = EnrollmentStatus::COMPLETED; $this->completedAt = $at ?? new \DateTimeImmutable(); return $this; }
    public function cancel(): static { $this->status = EnrollmentStatus::CANCELLED; return $this; }
    /** @return Collection<int, LessonProgress> */
    public function getLessonProgress(): Collection { return $this->lessonProgress; }
    public function addLessonProgress(LessonProgress $progress): static { if (!$this->lessonProgress->contains($progress)) { $this->lessonProgress->add($progress); $progress->setEnrollment($this); } return $this; }
    public function getCertificate(): ?Certificate { return $this->certificate; }
    public function setCertificate(?Certificate $certificate): static { $this->certificate = $certificate; return $this; }
}

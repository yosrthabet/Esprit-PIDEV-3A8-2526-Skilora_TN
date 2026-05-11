<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'formation_feedback')]
class FormationFeedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Formation $formation;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $student;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $overallRating = 3;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $contentRating = 3;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $trainerRating = 3;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $wouldRecommend = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getFormation(): Formation { return $this->formation; }
    public function setFormation(Formation $formation): static { $this->formation = $formation; return $this; }
    public function getStudent(): User { return $this->student; }
    public function setStudent(User $student): static { $this->student = $student; return $this; }
    public function getOverallRating(): int { return $this->overallRating; }
    public function setOverallRating(int $r): static { $this->overallRating = max(1, min(5, $r)); return $this; }
    public function getContentRating(): int { return $this->contentRating; }
    public function setContentRating(int $r): static { $this->contentRating = max(1, min(5, $r)); return $this; }
    public function getTrainerRating(): int { return $this->trainerRating; }
    public function setTrainerRating(int $r): static { $this->trainerRating = max(1, min(5, $r)); return $this; }
    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $c): static { $this->comment = $c; return $this; }
    public function getWouldRecommend(): bool { return $this->wouldRecommend; }
    public function setWouldRecommend(bool $w): static { $this->wouldRecommend = $w; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

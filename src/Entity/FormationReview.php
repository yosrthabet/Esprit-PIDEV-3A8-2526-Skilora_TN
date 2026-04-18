<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User review of a formation (one review per user per formation).
 */
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'formation_reviews')]
#[ORM\UniqueConstraint(name: 'formation_review_user_unique', columns: ['formation_id', 'user_id'])]
#[ORM\HasLifecycleCallbacks]
class FormationReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[Assert\Range(min: 1, max: 5)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $rating;

    #[Assert\Length(max: 1500)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'useful_count', options: ['default' => 0])]
    private int $usefulCount = 0;

    #[ORM\Column(name: 'not_useful_count', options: ['default' => 0])]
    private int $notUsefulCount = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = null !== $comment ? trim($comment) : null;

        return $this;
    }

    public function getUsefulCount(): int
    {
        return $this->usefulCount;
    }

    public function setUsefulCount(int $usefulCount): static
    {
        $this->usefulCount = max(0, $usefulCount);

        return $this;
    }

    public function incrementUsefulCount(): static
    {
        ++$this->usefulCount;

        return $this;
    }

    public function getNotUsefulCount(): int
    {
        return $this->notUsefulCount;
    }

    public function setNotUsefulCount(int $notUsefulCount): static
    {
        $this->notUsefulCount = max(0, $notUsefulCount);

        return $this;
    }

    public function incrementNotUsefulCount(): static
    {
        ++$this->notUsefulCount;

        return $this;
    }

    public function getHelpfulnessPercentage(): float
    {
        $total = $this->usefulCount + $this->notUsefulCount;
        if (0 === $total) {
            return 0.0;
        }

        return round(100 * $this->usefulCount / $total, 1);
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function touchCreatedAt(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}

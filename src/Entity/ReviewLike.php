<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReviewLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewLikeRepository::class)]
#[ORM\Table(name: 'formation_review_likes')]
#[ORM\UniqueConstraint(name: 'uniq_review_like_user_review', columns: ['review_id', 'user_id'])]
class ReviewLike
{
    public const VOTE_HELPFUL = 1;
    public const VOTE_NOT_HELPFUL = -1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FormationReview::class)]
    #[ORM\JoinColumn(name: 'review_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?FormationReview $review = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'smallint')]
    private int $vote = self::VOTE_HELPFUL;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReview(): ?FormationReview
    {
        return $this->review;
    }

    public function setReview(?FormationReview $review): static
    {
        $this->review = $review;

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

    public function getVote(): int
    {
        return $this->vote;
    }

    public function setVote(int $vote): static
    {
        $this->vote = $vote;

        return $this;
    }
}

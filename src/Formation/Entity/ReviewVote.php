<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Entity\User;
use App\Formation\Repository\ReviewVoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewVoteRepository::class)]
#[ORM\Table(name: 'formation_review_votes')]
#[ORM\UniqueConstraint(name: 'uniq_review_vote_user', columns: ['review_id', 'user_id'])]
class ReviewVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FormationReview::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FormationReview $review;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'is_helpful')]
    private bool $helpful = true;

    #[ORM\Column(name: 'voted_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $votedAt;

    public function __construct()
    {
        $this->votedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getReview(): FormationReview { return $this->review; }
    public function setReview(FormationReview $review): static { $this->review = $review; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function isHelpful(): bool { return $this->helpful; }
    public function setHelpful(bool $helpful): static { $this->helpful = $helpful; return $this; }
    public function getVotedAt(): \DateTimeImmutable { return $this->votedAt; }
}

<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Entity\Trait\BlameableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Finance\Repository\JobReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobReviewRepository::class)]
#[ORM\Table(name: 'job_reviews')]
#[ORM\UniqueConstraint(name: 'uniq_job_review', columns: ['contract_id', 'reviewer_id'])]
class JobReview
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contract::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contract $contract;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reviewer_id', nullable: false, onDelete: 'CASCADE')]
    private User $reviewer;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reviewed_user_id', nullable: false, onDelete: 'CASCADE')]
    private User $reviewedUser;

    #[ORM\Column]
    private int $rating = 5;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'communication_rating', nullable: true)]
    private ?int $communicationRating = null;

    #[ORM\Column(name: 'quality_rating', nullable: true)]
    private ?int $qualityRating = null;

    #[ORM\Column(name: 'timeliness_rating', nullable: true)]
    private ?int $timelinessRating = null;

    public function __construct()
    {
        $this->initTimestamps();
    }

    public function getId(): ?int { return $this->id; }
    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }
    public function getReviewer(): User { return $this->reviewer; }
    public function setReviewer(User $reviewer): static { $this->reviewer = $reviewer; return $this; }
    public function getReviewedUser(): User { return $this->reviewedUser; }
    public function setReviewedUser(User $reviewedUser): static { $this->reviewedUser = $reviewedUser; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): static { $this->rating = max(1, min(5, $rating)); return $this; }
    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): static { $this->comment = $comment; return $this; }
    public function getCommunicationRating(): ?int { return $this->communicationRating; }
    public function setCommunicationRating(?int $r): static { $this->communicationRating = $r !== null ? max(1, min(5, $r)) : null; return $this; }
    public function getQualityRating(): ?int { return $this->qualityRating; }
    public function setQualityRating(?int $r): static { $this->qualityRating = $r !== null ? max(1, min(5, $r)) : null; return $this; }
    public function getTimelinessRating(): ?int { return $this->timelinessRating; }
    public function setTimelinessRating(?int $r): static { $this->timelinessRating = $r !== null ? max(1, min(5, $r)) : null; return $this; }
    public function getAverageRating(): float
    {
        $ratings = array_filter([$this->rating, $this->communicationRating, $this->qualityRating, $this->timelinessRating], fn (?int $r) => $r !== null);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : (float) $this->rating;
    }
}

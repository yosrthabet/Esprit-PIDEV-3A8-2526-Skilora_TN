<?php

namespace App\Entity;

use App\Repository\FeedbackRepository;
use App\Validator\NoBadWords;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
#[ORM\Table(name: 'user_feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'feedbacks')]
    #[ORM\JoinColumn(name: 'ticket_id', nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    #[ORM\Column(name: 'user_id')]
    private ?int $userId = null;

    #[Assert\NotNull(message: 'Please tap a star to give a rating.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'The rating must be between 1 and 5 stars.')]
    #[ORM\Column]
    private ?int $rating = null;

    #[Assert\Length(max: 1500, maxMessage: 'Your comment is too long. Please keep it under 1500 characters.')]
    #[NoBadWords]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'feedback_type', length: 255, nullable: true)]
    private ?string $feedbackType = 'TICKET';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'is_resolved', nullable: true)]
    private ?bool $isResolved = null;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdDate = null;

    public function __construct()
    {
        $this->createdDate = new \DateTime();
        $this->feedbackType = 'TICKET';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getRating(): ?int
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
        $this->comment = $comment;

        return $this;
    }

    public function getFeedbackType(): ?string
    {
        return $this->feedbackType;
    }

    public function setFeedbackType(?string $feedbackType): static
    {
        $this->feedbackType = $feedbackType;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isResolved(): ?bool
    {
        return $this->isResolved;
    }

    public function setIsResolved(?bool $isResolved): static
    {
        $this->isResolved = $isResolved;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTimeInterface $createdDate): static
    {
        $this->createdDate = $createdDate;

        return $this;
    }
}

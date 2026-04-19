<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\NoBadWords;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'support_tickets')]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    #[ORM\Column(name: 'user_id')]
    private ?int $userId = null;

    #[Assert\NotBlank(message: 'Please provide a subject for this ticket.')]
    #[Assert\Length(min: 4, max: 255, minMessage: 'Subject must be at least 4 characters long.', maxMessage: 'Subject cannot be longer than 255 characters.')]
    #[Assert\Regex(pattern: '/[a-zA-Z]/', message: 'Subject must contain at least one letter.')]
    #[NoBadWords]
    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[Assert\NotBlank(message: 'Please select a category.')]
    #[Assert\Choice(choices: ['TECHNIQUE', 'FACTURATION', 'COMPTE', 'AUTRE'], message: 'Please select a valid category.')]
    #[ORM\Column(name: 'category', length: 50)]
    private ?string $category = null;

    #[Assert\NotBlank(message: 'Please select a priority.')]
    #[Assert\Choice(choices: ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], message: 'Please select a valid priority.')]
    #[ORM\Column(name: 'priority', length: 20)]
    private ?string $priority = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])]
    #[ORM\Column(name: 'status', length: 20)]
    private ?string $status = 'OPEN';

    #[Assert\NotBlank(message: 'Please describe your request in detail.')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'The description is too short. Please provide more details (at least 10 characters).', maxMessage: 'The description is too long.')]
    #[NoBadWords]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdDate = null;

    #[ORM\Column(name: 'resolved_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resolvedDate = null;

    #[ORM\Column(name: 'updated_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedDate = null;

    #[ORM\Column(name: 'sla_due_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $slaDueDate = null;

    #[ORM\Column(name: 'first_response_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $firstResponseDate = null;

    #[Assert\Positive]
    #[ORM\Column(name: 'assigned_to', nullable: true)]
    private ?int $assignedTo = null;

    /**
     * @var Collection<int, MessageTicket>
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: MessageTicket::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdDate' => 'ASC'])]
    private Collection $messages;

    /**
     * @var Collection<int, Feedback>
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: Feedback::class, cascade: ['persist', 'remove'])]
    private Collection $feedbacks;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->feedbacks = new ArrayCollection();
        $this->createdDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    public function getResolvedDate(): ?\DateTimeInterface
    {
        return $this->resolvedDate;
    }

    public function setResolvedDate(?\DateTimeInterface $resolvedDate): static
    {
        $this->resolvedDate = $resolvedDate;

        return $this;
    }

    public function getUpdatedDate(): ?\DateTimeInterface
    {
        return $this->updatedDate;
    }

    public function setUpdatedDate(?\DateTimeInterface $updatedDate): static
    {
        $this->updatedDate = $updatedDate;

        return $this;
    }

    public function getSlaDueDate(): ?\DateTimeInterface
    {
        return $this->slaDueDate;
    }

    public function setSlaDueDate(?\DateTimeInterface $slaDueDate): static
    {
        $this->slaDueDate = $slaDueDate;

        return $this;
    }

    public function getFirstResponseDate(): ?\DateTimeInterface
    {
        return $this->firstResponseDate;
    }

    public function setFirstResponseDate(?\DateTimeInterface $firstResponseDate): static
    {
        $this->firstResponseDate = $firstResponseDate;

        return $this;
    }

    public function getAssignedTo(): ?int
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?int $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    /**
     * @return Collection<int, MessageTicket>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(MessageTicket $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setTicket($this);
        }

        return $this;
    }

    public function removeMessage(MessageTicket $message): static
    {
        if ($this->messages->removeElement($message) && $message->getTicket() === $this) {
            $message->setTicket(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedbacks(): Collection
    {
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): static
    {
        if (!$this->feedbacks->contains($feedback)) {
            $this->feedbacks->add($feedback);
            $feedback->setTicket($this);
        }

        return $this;
    }

    public function removeFeedback(Feedback $feedback): static
    {
        if ($this->feedbacks->removeElement($feedback) && $feedback->getTicket() === $this) {
            $feedback->setTicket(null);
        }

        return $this;
    }
}

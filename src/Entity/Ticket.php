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
#[ORM\Table(name: 'ticket')]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    #[ORM\Column(name: 'utilisateur_id')]
    private ?int $utilisateurId = null;

    #[Assert\NotBlank(message: 'Please provide a subject for this ticket.')]
    #[Assert\Length(min: 4, max: 255, minMessage: 'Subject must be at least 4 characters long.', maxMessage: 'Subject cannot be longer than 255 characters.')]
    #[Assert\Regex(pattern: '/[a-zA-Z]/', message: 'Subject must contain at least one letter.')]
    #[NoBadWords]
    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[Assert\NotBlank(message: 'Please select a category.')]
    #[Assert\Choice(choices: ['TECHNIQUE', 'FACTURATION', 'COMPTE', 'AUTRE'], message: 'Please select a valid category.')]
    #[ORM\Column(length: 50)]
    private ?string $categorie = null;

    #[Assert\NotBlank(message: 'Please select a priority.')]
    #[Assert\Choice(choices: ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], message: 'Please select a valid priority.')]
    #[ORM\Column(length: 20)]
    private ?string $priorite = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])]
    #[ORM\Column(length: 20)]
    private ?string $statut = 'OPEN';

    #[Assert\NotBlank(message: 'Please describe your request in detail.')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'The description is too short. Please provide more details (at least 10 characters).', maxMessage: 'The description is too long.')]
    #[NoBadWords]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'date_resolution', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateResolution = null;

    #[Assert\Positive]
    #[ORM\Column(name: 'agent_id', nullable: true)]
    private ?int $agentId = null;

    /**
     * @var Collection<int, MessageTicket>
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: MessageTicket::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dateEnvoi' => 'ASC'])]
    private Collection $messages;

    #[ORM\OneToOne(mappedBy: 'ticket', targetEntity: Feedback::class, cascade: ['persist', 'remove'])]
    private ?Feedback $feedback = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateurId;
    }

    public function setUtilisateurId(int $utilisateurId): static
    {
        $this->utilisateurId = $utilisateurId;

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

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateResolution(): ?\DateTimeInterface
    {
        return $this->dateResolution;
    }

    public function setDateResolution(?\DateTimeInterface $dateResolution): static
    {
        $this->dateResolution = $dateResolution;

        return $this;
    }

    public function getAgentId(): ?int
    {
        return $this->agentId;
    }

    public function setAgentId(?int $agentId): static
    {
        $this->agentId = $agentId;

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

    public function getFeedback(): ?Feedback
    {
        return $this->feedback;
    }

    public function setFeedback(?Feedback $feedback): static
    {
        if ($feedback && $feedback->getTicket() !== $this) {
            $feedback->setTicket($this);
        }
        $this->feedback = $feedback;

        return $this;
    }
}

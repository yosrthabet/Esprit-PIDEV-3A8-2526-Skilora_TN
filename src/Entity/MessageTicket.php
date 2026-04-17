<?php

namespace App\Entity;

use App\Repository\MessageTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\NoBadWords;

#[ORM\Entity(repositoryClass: MessageTicketRepository::class)]
#[ORM\Table(name: 'ticket_messages')]
class MessageTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'ticket_id', nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    #[ORM\Column(name: 'utilisateur_id')]
    private ?int $utilisateurId = null;

    #[Assert\NotBlank(message: 'Your message cannot be empty.')]
    #[Assert\Length(min: 1, max: 3000, maxMessage: 'Your message is too long (max 3000 chars).')]
    #[NoBadWords]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(name: 'date_envoi', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateEnvoi = null;

    #[ORM\Column(name: 'is_internal', nullable: true)]
    private ?bool $isInternal = false;

    #[Assert\Length(max: 4000, maxMessage: 'Invalid attachment data too large.')]
    #[ORM\Column(name: 'attachments_json', type: Types::TEXT, nullable: true)]
    private ?string $attachmentsJson = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sentiment = null;

    public function __construct()
    {
        $this->dateEnvoi = new \DateTime();
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

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateurId;
    }

    public function setUtilisateurId(int $utilisateurId): static
    {
        $this->utilisateurId = $utilisateurId;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDateEnvoi(): ?\DateTimeInterface
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(\DateTimeInterface $dateEnvoi): static
    {
        $this->dateEnvoi = $dateEnvoi;

        return $this;
    }

    public function isInternal(): bool
    {
        return (bool) $this->isInternal;
    }

    public function setIsInternal(?bool $isInternal): static
    {
        $this->isInternal = $isInternal;

        return $this;
    }

    public function getAttachmentsJson(): ?string
    {
        return $this->attachmentsJson;
    }

    public function setAttachmentsJson(?string $attachmentsJson): static
    {
        $this->attachmentsJson = $attachmentsJson;

        return $this;
    }

    public function getSentiment(): ?string
    {
        return $this->sentiment;
    }

    public function setSentiment(?string $sentiment): static
    {
        $this->sentiment = $sentiment;

        return $this;
    }
}

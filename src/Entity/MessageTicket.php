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
    #[ORM\Column(name: 'sender_id')]
    private ?int $senderId = null;

    #[Assert\NotBlank(message: 'Your message cannot be empty.')]
    #[Assert\Length(min: 1, max: 3000, maxMessage: 'Your message is too long (max 3000 chars).')]
    #[NoBadWords]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdDate = null;

    #[ORM\Column(name: 'is_internal', nullable: true)]
    private ?bool $isInternal = false;

    #[Assert\Length(max: 4000, maxMessage: 'Invalid attachment data too large.')]
    #[ORM\Column(name: 'attachments_json', type: Types::TEXT, nullable: true)]
    private ?string $attachmentsJson = null;

    #[ORM\Column(name: 'audio_path', type: Types::TEXT, nullable: true)]
    private ?string $audioPath = null;

    #[ORM\Column(name: 'is_audio', nullable: true)]
    private ?bool $isAudio = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sentiment = null;

    public function __construct()
    {
        $this->createdDate = new \DateTime();
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

    public function getSenderId(): ?int
    {
        return $this->senderId;
    }

    public function setSenderId(int $senderId): static
    {
        $this->senderId = $senderId;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

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

    public function getAudioPath(): ?string
    {
        return $this->audioPath;
    }

    public function setAudioPath(?string $audioPath): static
    {
        $this->audioPath = $audioPath;

        return $this;
    }

    public function isAudio(): ?bool
    {
        return $this->isAudio;
    }

    public function setIsAudio(?bool $isAudio): static
    {
        $this->isAudio = $isAudio;

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

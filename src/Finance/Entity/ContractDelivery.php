<?php

declare(strict_types=1);

namespace App\Finance\Entity;

use App\Entity\User;
use App\Finance\Repository\ContractDeliveryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractDeliveryRepository::class)]
#[ORM\Table(name: 'finance_contract_deliveries')]
class ContractDelivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contract::class, inversedBy: 'deliveries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contract $contract;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $submittedBy;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    #[ORM\Column(name: 'attachment_url', length: 2048, nullable: true)]
    private ?string $attachmentUrl = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }
    public function getSubmittedBy(): User { return $this->submittedBy; }
    public function setSubmittedBy(User $submittedBy): static { $this->submittedBy = $submittedBy; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = trim($title); return $this; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): static { $this->message = trim($message); return $this; }
    public function getAttachmentUrl(): ?string { return $this->attachmentUrl; }
    public function setAttachmentUrl(?string $attachmentUrl): static { $this->attachmentUrl = $attachmentUrl !== null ? trim($attachmentUrl) : null; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

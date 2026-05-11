<?php

declare(strict_types=1);

namespace App\Support\Entity;

use App\Entity\User;
use App\Support\Repository\TicketAttachmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketAttachmentRepository::class)]
#[ORM\Table(name: 'support_ticket_attachments')]
class TicketAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportTicket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SupportTicket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'uploaded_by', nullable: false, onDelete: 'CASCADE')]
    private User $uploadedBy;

    #[ORM\Column(name: 'original_name', length: 255)]
    private string $originalName = '';

    #[ORM\Column(name: 'stored_path', length: 512)]
    private string $storedPath = '';

    #[ORM\Column(name: 'mime_type', length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(name: 'file_size')]
    private int $fileSize = 0;

    #[ORM\Column(name: 'uploaded_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTicket(): SupportTicket { return $this->ticket; }
    public function setTicket(SupportTicket $ticket): static { $this->ticket = $ticket; return $this; }
    public function getUploadedBy(): User { return $this->uploadedBy; }
    public function setUploadedBy(User $user): static { $this->uploadedBy = $user; return $this; }
    public function getOriginalName(): string { return $this->originalName; }
    public function setOriginalName(string $name): static { $this->originalName = $name; return $this; }
    public function getStoredPath(): string { return $this->storedPath; }
    public function setStoredPath(string $path): static { $this->storedPath = $path; return $this; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): static { $this->mimeType = $mimeType; return $this; }
    public function getFileSize(): int { return $this->fileSize; }
    public function setFileSize(int $fileSize): static { $this->fileSize = $fileSize; return $this; }
    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->fileSize;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    public function isImage(): bool
    {
        return $this->mimeType !== null && str_starts_with($this->mimeType, 'image/');
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\MemberInvitationStatus;
use App\Community\Repository\MemberInvitationRepository;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MemberInvitationRepository::class)]
#[ORM\Table(name: 'community_member_invitations')]
#[ORM\Index(columns: ['inviter_id'], name: 'idx_community_member_inviter')]
#[ORM\Index(columns: ['invitee_id'], name: 'idx_community_member_invitee')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_community_member_status_created')]
#[ORM\Index(columns: ['invitee_id', 'status'], name: 'idx_community_member_invitee_status')]
#[ORM\Index(columns: ['inviter_id', 'status'], name: 'idx_community_member_inviter_status')]
class MemberInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $inviter;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $invitee;

    #[ORM\Column(length: 20, enumType: MemberInvitationStatus::class)]
    private MemberInvitationStatus $status = MemberInvitationStatus::PENDING;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'responded_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getInviter(): User { return $this->inviter; }
    public function setInviter(User $inviter): static { $this->inviter = $inviter; return $this; }
    public function getInvitee(): User { return $this->invitee; }
    public function setInvitee(User $invitee): static { $this->invitee = $invitee; return $this; }
    public function getStatus(): MemberInvitationStatus { return $this->status; }
    public function setStatus(MemberInvitationStatus $status): static { $this->status = $status; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $note = trim($note ?? ''); $this->note = $note !== '' ? substr($note, 0, 500) : null; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getRespondedAt(): ?\DateTimeImmutable { return $this->respondedAt; }
    public function recordResponse(?\DateTimeImmutable $respondedAt = null): static { $this->respondedAt = $respondedAt ?? new \DateTimeImmutable(); return $this; }
    public function isPending(): bool { return $this->status === MemberInvitationStatus::PENDING; }
}

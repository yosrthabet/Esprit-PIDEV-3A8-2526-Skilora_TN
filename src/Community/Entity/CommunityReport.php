<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\CommunityReportStatus;
use App\Community\Repository\CommunityReportRepository;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityReportRepository::class)]
#[ORM\Table(name: 'community_feed_reports')]
#[ORM\UniqueConstraint(name: 'uniq_community_report_user_post', columns: ['post_id', 'reporter_id'])]
class CommunityReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityPost::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CommunityPost $post;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reporter_id', nullable: false, onDelete: 'CASCADE')]
    private User $reporter;

    #[ORM\Column(length: 120)]
    private string $reason = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 20, enumType: CommunityReportStatus::class)]
    private CommunityReportStatus $status = CommunityReportStatus::OPEN;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getPost(): CommunityPost { return $this->post; }
    public function setPost(CommunityPost $post): static { $this->post = $post; return $this; }
    public function getReporter(): User { return $this->reporter; }
    public function setReporter(User $reporter): static { $this->reporter = $reporter; return $this; }
    public function getReason(): string { return $this->reason; }
    public function setReason(string $reason): static { $this->reason = substr(trim($reason), 0, 120); return $this; }
    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): static { $details = trim($details ?? ''); $this->details = $details !== '' ? $details : null; return $this; }
    public function getStatus(): CommunityReportStatus { return $this->status; }
    public function setStatus(CommunityReportStatus $status): static { $this->status = $status; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

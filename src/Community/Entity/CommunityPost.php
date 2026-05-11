<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\CommunityPostStatus;
use App\Community\CommunityPostVisibility;
use App\Community\Repository\CommunityPostRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityPostRepository::class)]
#[ORM\Table(name: 'community_feed_posts')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_community_feed_status_created')]
class CommunityPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\ManyToOne(targetEntity: CommunityGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', nullable: true, onDelete: 'CASCADE')]
    private ?CommunityGroup $group = null;

    #[ORM\Column(length: 20, enumType: CommunityPostVisibility::class, options: ['default' => 'public'])]
    private CommunityPostVisibility $visibility = CommunityPostVisibility::PUBLIC;

    #[ORM\Column(name: 'media_path', length: 512, nullable: true)]
    private ?string $mediaPath = null;

    #[ORM\Column(length: 20, enumType: CommunityPostStatus::class)]
    private CommunityPostStatus $status = CommunityPostStatus::PUBLISHED;

    #[ORM\Column(name: 'likes_count')]
    private int $likesCount = 0;

    #[ORM\Column(name: 'comments_count')]
    private int $commentsCount = 0;

    #[ORM\Column(name: 'shares_count', options: ['default' => 0])]
    private int $sharesCount = 0;

    #[ORM\Column(name: 'reports_count', options: ['default' => 0])]
    private int $reportsCount = 0;

    #[ORM\Column(name: 'moderation_reason', type: Types::TEXT, nullable: true)]
    private ?string $moderationReason = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'pinned_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $pinnedAt = null;

    /** @var Collection<int, CommunityComment> */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: CommunityComment::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $comments;

    /** @var Collection<int, CommunityLike> */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: CommunityLike::class, orphanRemoval: true)]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getAuthor(): User { return $this->author; }
    public function setAuthor(User $author): static { $this->author = $author; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = trim($content); $this->touch(); return $this; }
    public function getGroup(): ?CommunityGroup { return $this->group; }
    public function setGroup(?CommunityGroup $group): static { $this->group = $group; $this->touch(); return $this; }
    public function getVisibility(): CommunityPostVisibility { return $this->visibility; }
    public function setVisibility(CommunityPostVisibility $visibility): static { $this->visibility = $visibility; $this->touch(); return $this; }
    public function getMediaPath(): ?string { return $this->mediaPath; }
    public function setMediaPath(?string $mediaPath): static { $mediaPath = trim($mediaPath ?? ''); $this->mediaPath = $mediaPath !== '' ? $mediaPath : null; $this->touch(); return $this; }
    public function getStatus(): CommunityPostStatus { return $this->status; }
    public function setStatus(CommunityPostStatus $status): static { $this->status = $status; $this->touch(); return $this; }
    public function getLikesCount(): int { return $this->likesCount; }
    public function incrementLikes(): void { $this->likesCount++; $this->touch(); }
    public function decrementLikes(): void { $this->likesCount = max(0, $this->likesCount - 1); $this->touch(); }
    public function getCommentsCount(): int { return $this->commentsCount; }
    public function incrementComments(): void { $this->commentsCount++; $this->touch(); }
    public function decrementComments(): void { $this->commentsCount = max(0, $this->commentsCount - 1); $this->touch(); }
    public function getSharesCount(): int { return $this->sharesCount; }
    public function incrementShares(): void { $this->sharesCount++; $this->touch(); }
    public function getReportsCount(): int { return $this->reportsCount; }
    public function incrementReports(): void { $this->reportsCount++; $this->touch(); }
    public function getModerationReason(): ?string { return $this->moderationReason; }
    public function setModerationReason(?string $moderationReason): static { $this->moderationReason = $moderationReason; $this->touch(); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getPinnedAt(): ?\DateTimeImmutable { return $this->pinnedAt; }
    public function pin(?\DateTimeImmutable $pinnedAt = null): static { $this->pinnedAt = $pinnedAt ?? new \DateTimeImmutable(); $this->touch(); return $this; }
    public function unpin(): static { $this->pinnedAt = null; $this->touch(); return $this; }
    /** @return Collection<int, CommunityComment> */
    public function getComments(): Collection { return $this->comments; }
    /** @return Collection<int, CommunityLike> */
    public function getLikes(): Collection { return $this->likes; }
    public function isVisible(): bool { return $this->status === CommunityPostStatus::PUBLISHED; }
    public function isGroupPost(): bool { return $this->group !== null; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

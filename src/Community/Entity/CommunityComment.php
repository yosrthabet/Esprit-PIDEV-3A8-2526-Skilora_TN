<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\Repository\CommunityCommentRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityCommentRepository::class)]
#[ORM\Table(name: 'community_feed_comments')]
#[ORM\Index(columns: ['post_id', 'created_at'], name: 'idx_community_comment_post_created')]
class CommunityComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityPost::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CommunityPost $post;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $replies;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->replies = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getPost(): CommunityPost { return $this->post; }
    public function setPost(CommunityPost $post): static { $this->post = $post; return $this; }
    public function getAuthor(): User { return $this->author; }
    public function setAuthor(User $author): static { $this->author = $author; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = trim($content); return $this; }
    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static { $this->parent = $parent; return $this; }
    /** @return Collection<int, self> */
    public function getReplies(): Collection { return $this->replies; }
    public function isReply(): bool { return $this->parent !== null; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function markEdited(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function isEdited(): bool { return $this->updatedAt !== null; }
}

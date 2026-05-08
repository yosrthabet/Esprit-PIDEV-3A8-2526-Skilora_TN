<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\BlogArticleStatus;
use App\Community\Repository\BlogArticleRepository;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlogArticleRepository::class)]
#[ORM\Table(name: 'community_blog_articles')]
#[ORM\UniqueConstraint(name: 'uniq_community_blog_slug', columns: ['slug'])]
#[ORM\Index(columns: ['status', 'published_at'], name: 'idx_community_blog_status_published')]
class BlogArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(length: 220)]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(length: 20, enumType: BlogArticleStatus::class)]
    private BlogArticleStatus $status = BlogArticleStatus::PUBLISHED;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->publishedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAuthor(): User { return $this->author; }
    public function setAuthor(User $author): static { $this->author = $author; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = trim($title); $this->touch(); return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = trim($slug); $this->touch(); return $this; }
    public function getExcerpt(): ?string { return $this->excerpt; }
    public function setExcerpt(?string $excerpt): static { $this->excerpt = $excerpt !== null ? trim($excerpt) : null; $this->touch(); return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = trim($content); $this->touch(); return $this; }
    public function getStatus(): BlogArticleStatus { return $this->status; }
    public function setStatus(BlogArticleStatus $status): static { $this->status = $status; $this->publishedAt = $status === BlogArticleStatus::PUBLISHED ? ($this->publishedAt ?? new \DateTimeImmutable()) : null; $this->touch(); return $this; }
    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function isPublished(): bool { return $this->status === BlogArticleStatus::PUBLISHED; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

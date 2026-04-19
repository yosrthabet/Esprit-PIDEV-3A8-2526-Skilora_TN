<?php

namespace App\Entity;

use App\Repository\BlogArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogArticleRepository::class)]
#[ORM\Table(name: 'blog_articles')]
#[ORM\HasLifecycleCallbacks]
class BlogArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.')]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu ne peut pas être vide.')]
    #[Assert\Length(min: 10, max: 50000)]
    private string $content = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $summary = null;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url(protocols: ['http', 'https'])]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $viewsCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $likesCount = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): self { $this->author = $author; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = trim($title); return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): self { $this->content = trim($content); return $this; }

    public function getSummary(): ?string { return $this->summary; }
    public function setSummary(?string $summary): self { $this->summary = $summary ? trim($summary) : null; return $this; }

    public function getCoverImageUrl(): ?string { return $this->coverImageUrl; }
    public function setCoverImageUrl(?string $url): self { $t = $url ? trim($url) : ''; $this->coverImageUrl = $t === '' ? null : $t; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): self { $this->category = $category; return $this; }

    public function getTags(): ?string { return $this->tags; }
    public function setTags(?string $tags): self { $this->tags = $tags; return $this; }

    public function getViewsCount(): int { return $this->viewsCount; }
    public function setViewsCount(int $count): self { $this->viewsCount = $count; return $this; }
    public function incrementViews(): self { $this->viewsCount++; return $this; }

    public function getLikesCount(): int { return $this->likesCount; }
    public function setLikesCount(int $count): self { $this->likesCount = $count; return $this; }

    public function isPublished(): bool { return $this->isPublished; }
    public function setIsPublished(bool $published): self {
        $this->isPublished = $published;
        if ($published && $this->publishedDate === null) {
            $this->publishedDate = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getPublishedDate(): ?\DateTimeImmutable { return $this->publishedDate; }
    public function setPublishedDate(?\DateTimeImmutable $date): self { $this->publishedDate = $date; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** @return string[] */
    public function getTagsArray(): array
    {
        if (!$this->tags) return [];
        return array_map('trim', explode(',', $this->tags));
    }
}

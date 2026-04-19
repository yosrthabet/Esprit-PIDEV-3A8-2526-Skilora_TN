<?php

namespace App\Entity;

use App\Repository\CommunityPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommunityPostRepository::class)]
#[ORM\Table(name: 'community_posts')]
#[ORM\HasLifecycleCallbacks]
class CommunityPost
{
    public const TYPE_STATUS = 'STATUS';
    public const TYPE_ARTICLE_SHARE = 'ARTICLE_SHARE';
    public const TYPE_JOB_SHARE = 'JOB_SHARE';
    public const TYPE_ACHIEVEMENT = 'ACHIEVEMENT';
    public const TYPE_SUCCESS_STORY = 'SUCCESS_STORY';

    public const POST_TYPES = [
        'Statut' => self::TYPE_STATUS,
        'Partage d\'article' => self::TYPE_ARTICLE_SHARE,
        'Partage d\'offre' => self::TYPE_JOB_SHARE,
        'Réalisation' => self::TYPE_ACHIEVEMENT,
        'Succès' => self::TYPE_SUCCESS_STORY,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'communityPosts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le texte de la publication ne peut pas être vide.')]
    #[Assert\Length(
        min: 1,
        max: 5000,
        minMessage: 'Le texte doit contenir au moins {{ limit }} caractère.',
        maxMessage: 'Le texte ne peut pas dépasser {{ limit }} caractères.'
    )]
    private string $content = '';

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true)]
    #[Assert\Length(max: 2048, maxMessage: 'Le lien ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Url(message: 'Veuillez saisir une URL valide (ex. https://…).', protocols: ['http', 'https'])]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 30, options: ['default' => 'STATUS'])]
    private string $postType = self::TYPE_STATUS;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $likesCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $commentsCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $sharesCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, PostComment> */
    #[ORM\OneToMany(targetEntity: PostComment::class, mappedBy: 'post', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $comments;

    /** @var Collection<int, PostLike> */
    #[ORM\OneToMany(targetEntity: PostLike::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $t = $imageUrl !== null ? trim($imageUrl) : '';
        $this->imageUrl = $t === '' ? null : $t;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPostType(): string
    {
        return $this->postType;
    }

    public function setPostType(string $postType): self
    {
        $this->postType = $postType;
        return $this;
    }

    public function getPostTypeLabel(): string
    {
        return array_search($this->postType, self::POST_TYPES, true) ?: $this->postType;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): self
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function getCommentsCount(): int
    {
        return $this->commentsCount;
    }

    public function setCommentsCount(int $commentsCount): self
    {
        $this->commentsCount = $commentsCount;
        return $this;
    }

    public function getSharesCount(): int
    {
        return $this->sharesCount;
    }

    public function setSharesCount(int $sharesCount): self
    {
        $this->sharesCount = $sharesCount;
        return $this;
    }

    /** @return Collection<int, PostComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, PostLike> */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function isLikedBy(User $user): bool
    {
        foreach ($this->likes as $like) {
            if ($like->getUser()->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }
}

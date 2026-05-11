<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\Repository\CommunityShareRepository;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityShareRepository::class)]
#[ORM\Table(name: 'community_feed_shares')]
#[ORM\UniqueConstraint(name: 'uniq_share_user_post', columns: ['user_id', 'post_id'])]
class CommunityShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityPost::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CommunityPost $post;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getPost(): CommunityPost { return $this->post; }
    public function setPost(CommunityPost $post): static { $this->post = $post; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

<?php

namespace App\Entity;

use App\Repository\GroupMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupMemberRepository::class)]
#[ORM\Table(name: 'group_members')]
#[ORM\UniqueConstraint(name: 'unique_group_user', columns: ['group_id', 'user_id'])]
class GroupMember
{
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_MODERATOR = 'MODERATOR';
    public const ROLE_MEMBER = 'MEMBER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityGroup::class, inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'group_id', nullable: false, onDelete: 'CASCADE')]
    private ?CommunityGroup $group = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column(name: 'joined_date', type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getGroup(): ?CommunityGroup { return $this->group; }
    public function setGroup(?CommunityGroup $group): self { $this->group = $group; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getJoinedAt(): \DateTimeImmutable { return $this->joinedAt; }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
    public function isModerator(): bool { return $this->role === self::ROLE_MODERATOR; }
}

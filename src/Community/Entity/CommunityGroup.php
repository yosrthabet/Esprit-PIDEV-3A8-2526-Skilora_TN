<?php

declare(strict_types=1);

namespace App\Community\Entity;

use App\Community\Repository\CommunityGroupRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityGroupRepository::class)]
#[ORM\Table(name: 'community_spaces_groups')]
#[ORM\Index(columns: ['created_at'], name: 'idx_community_group_created')]
class CommunityGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: 160)]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, options: ['default' => 'public'])]
    private string $privacy = 'public';

    #[ORM\Column(name: 'image_path', length: 512, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(name: 'members_count')]
    private int $membersCount = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, GroupMember> */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMember::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $members;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getOwner(): User { return $this->owner; }
    public function setOwner(User $owner): static { $this->owner = $owner; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = trim($name); $this->touch(); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description !== null ? trim($description) : null; $this->touch(); return $this; }
    public function getPrivacy(): string { return $this->privacy; }
    public function setPrivacy(string $privacy): static { $this->privacy = in_array($privacy, ['public', 'private'], true) ? $privacy : 'public'; $this->touch(); return $this; }
    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): static { $imagePath = trim($imagePath ?? ''); $this->imagePath = $imagePath !== '' ? $imagePath : null; $this->touch(); return $this; }
    public function getMembersCount(): int { return $this->membersCount; }
    public function incrementMembers(): void { $this->membersCount++; $this->touch(); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    /** @return Collection<int, GroupMember> */
    public function getMembers(): Collection { return $this->members; }
    public function addMember(User $user, string $role = 'member'): GroupMember { $member = (new GroupMember())->setGroup($this)->setUser($user)->setRole($role); $this->members->add($member); $this->incrementMembers(); return $member; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

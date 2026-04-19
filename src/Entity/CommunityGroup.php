<?php

namespace App\Entity;

use App\Repository\CommunityGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommunityGroupRepository::class)]
#[ORM\Table(name: 'community_groups')]
class CommunityGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du groupe est requis.')]
    #[Assert\Length(min: 3, max: 255)]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url(protocols: ['http', 'https'])]
    private ?string $coverImageUrl = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $creator = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $memberCount = 1;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isPublic = true;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, GroupMember> */
    #[ORM\OneToMany(targetEntity: GroupMember::class, mappedBy: 'group', orphanRemoval: true)]
    private Collection $members;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): self { $this->category = $category; return $this; }

    public function getCoverImageUrl(): ?string { return $this->coverImageUrl; }
    public function setCoverImageUrl(?string $url): self { $t = $url ? trim($url) : ''; $this->coverImageUrl = $t === '' ? null : $t; return $this; }

    public function getCreator(): ?User { return $this->creator; }
    public function setCreator(?User $creator): self { $this->creator = $creator; return $this; }

    public function getMemberCount(): int { return $this->memberCount; }
    public function setMemberCount(int $count): self { $this->memberCount = $count; return $this; }

    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): self { $this->isPublic = $isPublic; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, GroupMember> */
    public function getMembers(): Collection { return $this->members; }
}

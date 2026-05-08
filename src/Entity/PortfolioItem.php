<?php

namespace App\Entity;

use App\Entity\Embeddable\DateRange;
use App\Repository\PortfolioItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PortfolioItemRepository::class)]
#[ORM\Table(name: 'portfolio_items')]
class PortfolioItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'project_url', type: Types::TEXT, nullable: true)]
    private ?string $projectUrl = null;

    #[ORM\Column(name: 'image_url', type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $technologies = null;

    #[ORM\Embedded(class: DateRange::class, columnPrefix: false)]
    private DateRange $period;

    #[ORM\Column(name: 'is_featured', type: 'boolean', nullable: true, options: ['default' => false])]
    private ?bool $isFeatured = false;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdDate;

    public function __construct()
    {
        $this->period = new DateRange();
        $this->createdDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getProjectUrl(): ?string
    {
        return $this->projectUrl;
    }

    public function setProjectUrl(?string $projectUrl): static
    {
        $this->projectUrl = $projectUrl;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getTechnologies(): ?string
    {
        return $this->technologies;
    }

    public function setTechnologies(?string $technologies): static
    {
        $this->technologies = $technologies;
        return $this;
    }

    public function getPeriod(): DateRange
    {
        return $this->period;
    }

    public function setPeriod(DateRange $period): static
    {
        $this->period = $period;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->period->getStartDate();
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->period->setStartDate($startDate);
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->period->getEndDate();
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->period->setEndDate($endDate);
        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(?bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function getCreatedDate(): \DateTimeImmutable
    {
        return $this->createdDate;
    }

    protected function setCreatedDate(\DateTimeImmutable $createdDate): static
    {
        $this->createdDate = $createdDate;
        return $this;
    }
}

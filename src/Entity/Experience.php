<?php

namespace App\Entity;

use App\Entity\Embeddable\DateRange;
use App\Repository\ExperienceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExperienceRepository::class)]
#[ORM\Table(name: 'experiences')]
class Experience
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id', nullable: false)]
    private ?Profile $profile = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $position = null;

    #[ORM\Embedded(class: DateRange::class, columnPrefix: false)]
    private DateRange $period;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'current_job', type: 'boolean', nullable: true, options: ['default' => false])]
    private ?bool $currentJob = false;

    public function __construct()
    {
        $this->period = new DateRange();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isCurrentJob(): ?bool
    {
        return $this->currentJob;
    }

    public function setCurrentJob(?bool $currentJob): static
    {
        $this->currentJob = $currentJob;
        return $this;
    }
}

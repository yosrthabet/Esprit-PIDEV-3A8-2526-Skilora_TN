<?php

declare(strict_types=1);

namespace App\Recruitment\Entity;

use App\Entity\User;
use App\Recruitment\Repository\SavedJobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SavedJobRepository::class)]
#[ORM\Table(name: 'saved_jobs')]
#[ORM\UniqueConstraint(name: 'uniq_saved_job_user_offer', columns: ['user_id', 'job_offer_id'])]
class SavedJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: JobOffer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private JobOffer $jobOffer;

    #[ORM\Column(name: 'saved_at')]
    private \DateTimeImmutable $savedAt;

    public function __construct()
    {
        $this->savedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getJobOffer(): JobOffer { return $this->jobOffer; }
    public function setJobOffer(JobOffer $jobOffer): static { $this->jobOffer = $jobOffer; return $this; }
    public function getSavedAt(): \DateTimeImmutable { return $this->savedAt; }
}

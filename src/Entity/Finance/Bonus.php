<?php

namespace App\Entity\Finance;

use App\Entity\User;
use App\Repository\Finance\BonusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonusRepository::class)]
#[ORM\Table(name: 'bonuses')]
class Bonus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L’employé est obligatoire.')]
    private ?User $user = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Le montant est obligatoire.')]
    #[Assert\GreaterThanOrEqual(value: 0.01, message: 'Le montant doit être strictement positif.')]
    #[Assert\LessThanOrEqual(value: 999999999.99, message: 'Montant trop élevé.')]
    private ?float $amount = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le motif est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Motif trop long.')]
    private ?string $reason = null;

    #[ORM\Column(name: 'date_awarded', type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotNull(message: 'La date d’attribution est obligatoire.')]
    private ?\DateTimeImmutable $dateAwarded = null;

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

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason !== null ? trim($reason) : null;

        return $this;
    }

    public function getDateAwarded(): ?\DateTimeImmutable
    {
        return $this->dateAwarded;
    }

    public function setDateAwarded(?\DateTimeImmutable $dateAwarded): static
    {
        $this->dateAwarded = $dateAwarded;

        return $this;
    }
}

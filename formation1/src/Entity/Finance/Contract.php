<?php

namespace App\Entity\Finance;

use App\Entity\User;
use App\Recruitment\Entity\Company;
use App\Repository\Finance\ContractRepository;
use App\Validation\Finance\FinanceAllowedValues;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: 'contracts')]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L’employé est obligatoire.')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_Name', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'L’entreprise est obligatoire.')]
    private ?Company $company = null;

    #[ORM\Column(name: 'type', length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le type de contrat est obligatoire.')]
    #[Assert\Choice(choices: FinanceAllowedValues::CONTRACT_TYPES, message: 'Type de contrat non reconnu.')]
    private ?string $type = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Le poste est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Poste trop court.', maxMessage: 'Poste trop long.')]
    #[Assert\Regex(pattern: '/^[\p{L}0-9\s\-\.\/\'’]+$/u', message: 'Caractères non autorisés dans l’intitulé du poste.')]
    private ?string $position = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Le salaire est obligatoire.')]
    #[Assert\GreaterThanOrEqual(value: 0.01, message: 'Le salaire doit être strictement positif.')]
    #[Assert\LessThanOrEqual(value: 999999999.99, message: 'Montant trop élevé.')]
    private ?float $salary = null;

    #[ORM\Column(name: 'start_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(choices: FinanceAllowedValues::CONTRACT_STATUSES, message: 'Statut de contrat non reconnu.')]
    private ?string $status = null;

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->startDate && $this->endDate && $this->endDate < $this->startDate) {
            $context->buildViolation('La date de fin doit être après la date de début.')
                ->atPath('endDate')
                ->addViolation();
        }
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type !== null ? trim($type) : null;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position !== null ? trim($position) : null;

        return $this;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(?float $salary): static
    {
        $this->salary = $salary;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status !== null ? trim($status) : null;

        return $this;
    }

    /**
     * Ancienneté sur la période du contrat : de la date de début à la date de fin ;
     * si la fin est absente, jusqu’à aujourd’hui (contrat en cours).
     *
     * Libellé proche du module JavaFX : années entières en priorité (« 4 ans »), sinon mois, sinon jours.
     */
    public function getTenureLabel(): ?string
    {
        if (null === $this->startDate) {
            return null;
        }

        $start = $this->startDate;
        $end = $this->endDate ?? new \DateTimeImmutable('today');
        $end = \DateTimeImmutable::createFromInterface($end);

        if ($end < $start) {
            return null;
        }

        $interval = $start->diff($end);
        $years = (int) $interval->y;
        $months = (int) $interval->m;
        $days = (int) $interval->d;

        if ($years > 0) {
            return 1 === $years ? '1 an' : $years.' ans';
        }
        if ($months > 0) {
            return 1 === $months ? '1 mois' : $months.' mois';
        }
        if ($days > 0) {
            return 1 === $days ? '1 jour' : $days.' jours';
        }

        return 'Moins d’un jour';
    }
}

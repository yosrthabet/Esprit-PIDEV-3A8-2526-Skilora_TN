<?php

namespace App\Entity\Finance;

use App\Entity\User;
use App\Repository\Finance\PayslipRepository;
use App\Validation\Finance\FinanceAllowedValues;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PayslipRepository::class)]
#[ORM\Table(name: 'payslips')]
class Payslip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L’employé est obligatoire.')]
    private ?User $user = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotNull(message: 'Le mois est obligatoire.')]
    #[Assert\Range(min: 1, max: 12, notInRangeMessage: 'Mois invalide.')]
    private ?int $month = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotNull(message: 'L’année est obligatoire.')]
    #[Assert\Range(min: 2000, max: 2100, notInRangeMessage: 'Année invalide.')]
    private ?int $year = null;

    #[ORM\Column(name: 'base_salary', type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Le salaire de base est obligatoire.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le montant ne peut pas être négatif.')]
    #[Assert\LessThanOrEqual(value: 999999999.99, message: 'Montant trop élevé.')]
    private ?float $baseSalary = null;

    #[ORM\Column(name: 'overtime_hours', type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Les heures supplémentaires sont obligatoires (0 si aucune).')]
    #[Assert\Range(min: 0, max: 99999.99, notInRangeMessage: 'Montant invalide.')]
    private ?float $overtimeHours = null;

    #[ORM\Column(name: 'overtime_total', type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Le total heures sup. est obligatoire (0 si aucune).')]
    #[Assert\Range(min: 0, max: 999999999.99, notInRangeMessage: 'Montant invalide.')]
    private ?float $overtimeTotal = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Les primes additionnelles sont obligatoires (0 si aucune).')]
    #[Assert\Range(min: 0, max: 999999999.99, notInRangeMessage: 'Montant invalide.')]
    private ?float $bonuses = null;

    #[ORM\Column(name: 'other_deductions', type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Les autres retenues sont obligatoires (0 si aucune).')]
    #[Assert\Range(min: 0, max: 999999999.99, notInRangeMessage: 'Montant invalide.')]
    private ?float $otherDeductions = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\NotBlank(message: 'La devise est obligatoire.')]
    #[Assert\Choice(choices: FinanceAllowedValues::CURRENCIES, message: 'Devise non reconnue.')]
    private ?string $currency = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(choices: FinanceAllowedValues::PAYSLIP_STATUSES, message: 'Statut de bulletin non reconnu.')]
    private ?string $status = null;

    #[ORM\Column(name: 'deductions_json', type: Types::TEXT, nullable: true)]
    private ?string $deductionsJson = null;

    #[ORM\Column(name: 'bonuses_json', type: Types::TEXT, nullable: true)]
    private ?string $bonusesJson = null;

    #[Assert\Callback]
    public function validateOptionalJson(ExecutionContextInterface $context): void
    {
        foreach (['deductionsJson', 'bonusesJson'] as $prop) {
            $raw = $this->{$prop};
            if ($raw === null || trim((string) $raw) === '') {
                $context->buildViolation('Ce champ est obligatoire (saisissez [] pour aucune donnée).')
                    ->atPath($prop)
                    ->addViolation();

                continue;
            }
            json_decode((string) $raw, true);
            if (\JSON_ERROR_NONE !== json_last_error()) {
                $context->buildViolation('JSON invalide.')
                    ->atPath($prop)
                    ->addViolation();
            }
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

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(?int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getBaseSalary(): ?float
    {
        return $this->baseSalary;
    }

    public function setBaseSalary(?float $baseSalary): static
    {
        $this->baseSalary = $baseSalary;

        return $this;
    }

    public function getOvertimeHours(): ?float
    {
        return $this->overtimeHours;
    }

    public function setOvertimeHours(?float $overtimeHours): static
    {
        $this->overtimeHours = $overtimeHours;

        return $this;
    }

    public function getOvertimeTotal(): ?float
    {
        return $this->overtimeTotal;
    }

    public function setOvertimeTotal(?float $overtimeTotal): static
    {
        $this->overtimeTotal = $overtimeTotal;

        return $this;
    }

    public function getBonuses(): ?float
    {
        return $this->bonuses;
    }

    public function setBonuses(?float $bonuses): static
    {
        $this->bonuses = $bonuses;

        return $this;
    }

    public function getOtherDeductions(): ?float
    {
        return $this->otherDeductions;
    }

    public function setOtherDeductions(?float $otherDeductions): static
    {
        $this->otherDeductions = $otherDeductions;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDeductionsJson(): ?string
    {
        return $this->deductionsJson;
    }

    public function setDeductionsJson(?string $deductionsJson): static
    {
        $this->deductionsJson = $deductionsJson;

        return $this;
    }

    public function getBonusesJson(): ?string
    {
        return $this->bonusesJson;
    }

    public function setBonusesJson(?string $bonusesJson): static
    {
        $this->bonusesJson = $bonusesJson;

        return $this;
    }

    /** Base + overtime total + bonuses (not persisted). */
    public function getEstimatedGross(): float
    {
        return (float) ($this->baseSalary ?? 0)
            + (float) ($this->overtimeTotal ?? 0)
            + (float) ($this->bonuses ?? 0);
    }

    private const UI_CNSS_RATE = 0.0918;

    private const UI_IRPP_ON_TAXABLE_RATE = 0.26;

    /**
     * Net aligné module JavaFX (PayslipRow) : CNSS sur brut, IRPP 26 % sur (brut − CNSS), puis autres retenues.
     */
    public function getEstimatedNet(): float
    {
        $gross = $this->getEstimatedGross();
        $cnss = round($gross * self::UI_CNSS_RATE, 2);
        $taxable = max(0.0, $gross - $cnss);
        $irpp = round($taxable * self::UI_IRPP_ON_TAXABLE_RATE, 2);
        $other = (float) ($this->otherDeductions ?? 0);
        $totalDed = round($cnss + $irpp + $other, 2);

        return max(0.0, round($gross - $totalDed, 2));
    }

    public function getComputedCnss(): float
    {
        $gross = $this->getEstimatedGross();

        return round($gross * self::UI_CNSS_RATE, 2);
    }

    public function getComputedIrpp(): float
    {
        $gross = $this->getEstimatedGross();
        $cnss = $gross * self::UI_CNSS_RATE;
        $taxable = max(0.0, $gross - $cnss);

        return round($taxable * self::UI_IRPP_ON_TAXABLE_RATE, 2);
    }

    public function getComputedTotalDeductions(): float
    {
        return round(
            $this->getComputedCnss() + $this->getComputedIrpp() + (float) ($this->otherDeductions ?? 0),
            2
        );
    }
}

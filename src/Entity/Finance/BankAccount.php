<?php

namespace App\Entity\Finance;

use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use App\Entity\User;
use App\Repository\Finance\BankAccountRepository;
use App\Validation\Finance\FinanceAllowedValues;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Auditable]
#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
#[ORM\Table(
    name: 'bank_accounts',
    indexes: [
        new ORM\Index(name: 'idx_bank_accounts_user_id', columns: ['user_id']),
    ]
)]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L’employé est obligatoire.')]
    private ?User $user = null;

    #[ORM\Column(name: 'bank_name', length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Le nom de la banque est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Nom trop court.', maxMessage: 'Nom trop long.')]
    #[Assert\Regex(pattern: '/^[\p{L}\d\s\-\.&\'’(),]+$/u', message: 'Caractères non autorisés.')]
    private ?string $bankName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'L’IBAN est obligatoire.')]
    #[Assert\Length(max: 50)]
    private ?string $iban = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: 'Le code SWIFT/BIC est obligatoire.')]
    #[Assert\Length(min: 8, max: 11, minMessage: 'Le SWIFT/BIC doit faire 8 ou 11 caractères.', maxMessage: 'Le SWIFT/BIC ne peut pas dépasser 11 caractères.')]
    private ?string $swift = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\NotBlank(message: 'La devise est obligatoire.')]
    #[Assert\Choice(choices: FinanceAllowedValues::CURRENCIES, message: 'Devise non reconnue.')]
    private ?string $currency = null;

    #[ORM\Column(name: 'is_primary', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPrimary = false;

    #[ORM\Column(name: 'is_verified', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isVerified = false;

    #[Assert\Callback]
    public function validateIbanAndSwift(ExecutionContextInterface $context): void
    {
        $rawIban = $this->iban ?? '';
        $iban = strtoupper(preg_replace('/\s+/', '', $rawIban) ?? '');
        if ($iban !== '') {
            if (strlen($iban) < 15 || strlen($iban) > 34) {
                $context->buildViolation('IBAN : longueur invalide.')
                    ->atPath('iban')
                    ->addViolation();
            } elseif (!preg_match('/^[A-Z]{2}[0-9]{2}[0-9A-Z]+$/', $iban)) {
                $context->buildViolation('Format IBAN invalide.')
                    ->atPath('iban')
                    ->addViolation();
            }
        }

        $rawSwift = $this->swift ?? '';
        $swift = strtoupper(preg_replace('/\s+/', '', $rawSwift) ?? '');
        if ($swift !== '' && !preg_match('/^[A-Z0-9]{8}([A-Z0-9]{3})?$/', $swift)) {
            $context->buildViolation('Format SWIFT/BIC invalide (8 ou 11 caractères alphanumériques, ex. DEUTDEFF ou DEUTDEFF500).')
                ->atPath('swift')
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

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): static
    {
        $this->bankName = $bankName !== null ? trim($bankName) : null;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban !== null ? trim($iban) : null;

        return $this;
    }

    public function getSwift(): ?string
    {
        return $this->swift;
    }

    public function setSwift(?string $swift): static
    {
        $this->swift = $swift !== null ? trim($swift) : null;

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

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}

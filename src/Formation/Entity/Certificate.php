<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Formation\Repository\CertificateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CertificateRepository::class)]
#[ORM\Table(name: 'formation_certificates')]
#[ORM\UniqueConstraint(name: 'uniq_certificate_enrollment', columns: ['enrollment_id'])]
#[ORM\UniqueConstraint(name: 'uniq_certificate_verification_id', columns: ['verification_id'])]
class Certificate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'certificate', targetEntity: Enrollment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Enrollment $enrollment;

    #[ORM\Column(name: 'verification_id', length: 64)]
    private string $verificationId = '';

    #[ORM\Column(name: 'issued_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $issuedAt;

    public function __construct()
    {
        $this->issuedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEnrollment(): Enrollment { return $this->enrollment; }
    public function setEnrollment(Enrollment $enrollment): static { $this->enrollment = $enrollment; $enrollment->setCertificate($this); return $this; }
    public function getVerificationId(): string { return $this->verificationId; }
    public function setVerificationId(string $verificationId): static { $this->verificationId = $verificationId; return $this; }
    public function getIssuedAt(): \DateTimeImmutable { return $this->issuedAt; }
}

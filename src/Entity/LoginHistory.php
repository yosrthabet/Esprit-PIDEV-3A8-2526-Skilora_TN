<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'login_history')]
#[ORM\Index(columns: ['user_id'], name: 'idx_login_history_user')]
class LoginHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 20)]
    private string $status = 'success'; // 'success' | 'failure'

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $method = null; // 'password' | 'google' | 'github'

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $ip): static { $this->ip = $ip; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getMethod(): ?string { return $this->method; }
    public function setMethod(?string $method): static { $this->method = $method; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

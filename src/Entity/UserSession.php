<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_sessions')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_sessions_user')]
class UserSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 128, unique: true)]
    private ?string $sessionId = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastActivity;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastActivity = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getSessionId(): ?string { return $this->sessionId; }
    public function setSessionId(?string $sessionId): static { $this->sessionId = $sessionId; return $this; }

    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $ip): static { $this->ip = $ip; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }

    public function getLastActivity(): \DateTimeImmutable { return $this->lastActivity; }
    public function setLastActivity(\DateTimeImmutable $lastActivity): static { $this->lastActivity = $lastActivity; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getBrowserName(): string
    {
        $ua = $this->userAgent ?? '';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Edg')) return 'Edge';
        if (str_contains($ua, 'Chrome')) return 'Chrome';
        if (str_contains($ua, 'Safari')) return 'Safari';
        if (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) return 'Opera';
        return 'Unknown';
    }

    public function getDeviceType(): string
    {
        $ua = $this->userAgent ?? '';
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) return 'Mobile';
        return 'Desktop';
    }
}

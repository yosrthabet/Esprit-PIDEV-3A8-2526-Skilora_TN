<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FormationLevel;
use App\Repository\FormationRepository;
use App\Validation\ValidationGroups;
use App\Validator\FormationDurationConsistent;
use App\Validator\UniqueFormationTitle;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formations')]
#[ORM\HasLifecycleCallbacks]
#[FormationDurationConsistent(groups: [ValidationGroups::FORMATION_CREATE, ValidationGroups::FORMATION_UPDATE])]
#[UniqueFormationTitle(groups: [ValidationGroups::FORMATION_CREATE, ValidationGroups::FORMATION_UPDATE])]
class Formation
{
    private const FORMATION_GROUPS = [
        ValidationGroups::FORMATION_CREATE,
        ValidationGroups::FORMATION_UPDATE,
    ];

    public const CATEGORY_DEVELOPMENT = 'DEVELOPMENT';
    public const CATEGORY_DATA_SCIENCE = 'DATA_SCIENCE';
    public const CATEGORY_DESIGN = 'DESIGN';
    public const CATEGORY_LANGUAGES = 'LANGUAGES';
    public const CATEGORY_BUSINESS = 'BUSINESS';
    public const CATEGORY_OTHER = 'OTHER';

    /** @var array<string, string> */
    public const CATEGORY_LABELS_FR = [
        self::CATEGORY_DEVELOPMENT => 'Développement',
        self::CATEGORY_DATA_SCIENCE => 'Data Science',
        self::CATEGORY_DESIGN => 'Design',
        self::CATEGORY_LANGUAGES => 'Langues',
        self::CATEGORY_BUSINESS => 'Business',
        self::CATEGORY_OTHER => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'formation.title.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Length(min: 3, max: 255, minMessage: 'formation.title.min', maxMessage: 'formation.title.max', groups: self::FORMATION_GROUPS)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'formation.description.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Length(min: 20, max: 500, minMessage: 'formation.description.min', maxMessage: 'formation.description.max', groups: self::FORMATION_GROUPS)]
    private ?string $description = null;

    #[ORM\Column(name: 'cost', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'formation.price.positive_or_zero', groups: self::FORMATION_GROUPS)]
    private ?string $price = null;

    #[ORM\Column(name: 'currency', length: 10, nullable: true)]
    private ?string $currency = 'TND';

    #[ORM\Column(name: 'duration_hours', nullable: true)]
    #[Assert\NotBlank(message: 'formation.duration.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Positive(message: 'formation.duration.positive', groups: self::FORMATION_GROUPS)]
    private ?int $duration = null;

    #[ORM\Column(name: 'lesson_count', nullable: true)]
    #[Assert\NotBlank(message: 'formation.lessons_count.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Positive(message: 'formation.lessons_count.positive', groups: self::FORMATION_GROUPS)]
    private ?int $lessonsCount = null;

    #[ORM\Column(length: 32, nullable: true, enumType: FormationLevel::class)]
    #[Assert\NotNull(message: 'formation.level.not_null', groups: self::FORMATION_GROUPS)]
    private FormationLevel $level = FormationLevel::BEGINNER;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank(message: 'formation.category.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Choice(callback: [self::class, 'getCategoryKeys'], message: 'formation.category.choice', groups: self::FORMATION_GROUPS)]
    private string $category = self::CATEGORY_DEVELOPMENT;

    #[ORM\Column(name: 'provider', length: 255, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(name: 'image_url', type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'is_free', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isFree = true;

    #[ORM\Column(name: 'created_by', nullable: true)]
    private ?int $createdBy = null;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'status', length: 20, nullable: true)]
    private ?string $status = 'ACTIVE';

    #[ORM\Column(name: 'director_signature', type: Types::TEXT, nullable: true)]
    private ?string $directorSignature = null;

    /** Stored filename for the certificate director signature image (PNG), e.g. "signature.png". */
    #[ORM\Column(name: 'certificate_signature_filename', length: 255, nullable: true)]
    private ?string $certificateSignatureFilename = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim(strip_tags($title));

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description !== null ? trim(strip_tags($description)) : null;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price !== null ? (float) $this->price : null;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price !== null ? (string) $price : null;

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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getLessonsCount(): ?int
    {
        return $this->lessonsCount;
    }

    public function setLessonsCount(?int $lessonsCount): static
    {
        $this->lessonsCount = $lessonsCount;

        return $this;
    }

    public function getLevel(): FormationLevel
    {
        return $this->level;
    }

    public function setLevel(FormationLevel $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

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

    public function isFree(): ?bool
    {
        return $this->isFree;
    }

    public function setIsFree(?bool $isFree): static
    {
        $this->isFree = $isFree;

        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    public function getDirectorSignature(): ?string
    {
        return $this->directorSignature;
    }

    public function setDirectorSignature(?string $directorSignature): static
    {
        $this->directorSignature = $directorSignature;

        return $this;
    }

    public function getCertificateSignatureFilename(): ?string
    {
        return $this->certificateSignatureFilename;
    }

    public function setCertificateSignatureFilename(?string $certificateSignatureFilename): static
    {
        $this->certificateSignatureFilename = $certificateSignatureFilename;

        return $this;
    }

    public function getCategoryLabelFr(): string
    {
        return self::CATEGORY_LABELS_FR[$this->category] ?? $this->category;
    }

    public function getLevelLabelFr(): string
    {
        return $this->level->labelFr();
    }

    public function getPriceDisplayFr(): string
    {
        $price = $this->getPrice();
        if (null === $price) {
            return '—';
        }
        if ($price <= 0.0) {
            return 'Gratuit';
        }

        return number_format($price, 2, ',', "\u{202f}");
    }

    /**
     * @return list<string>
     */
    public static function getCategoryKeys(): array
    {
        return [
            self::CATEGORY_DEVELOPMENT,
            self::CATEGORY_DATA_SCIENCE,
            self::CATEGORY_DESIGN,
            self::CATEGORY_LANGUAGES,
            self::CATEGORY_BUSINESS,
            self::CATEGORY_OTHER,
        ];
    }

    #[ORM\PrePersist]
    public function touchCreatedAt(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }
}

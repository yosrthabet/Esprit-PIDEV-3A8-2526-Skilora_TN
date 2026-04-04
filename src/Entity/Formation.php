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
    /**
     * Property + class constraints for Symfony forms (create vs update).
     *
     * @var list<string>
     */
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

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'formation.description.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Length(min: 20, max: 500, minMessage: 'formation.description.min', maxMessage: 'formation.description.max', groups: self::FORMATION_GROUPS)]
    private string $description = '';

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\PositiveOrZero(message: 'formation.price.positive_or_zero', groups: self::FORMATION_GROUPS)]
    private ?float $price = null;

    #[ORM\Column(name: 'duration_hours')]
    #[Assert\NotBlank(message: 'formation.duration.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Type(type: 'integer', message: 'formation.duration.type_integer', groups: self::FORMATION_GROUPS)]
    #[Assert\Positive(message: 'formation.duration.positive', groups: self::FORMATION_GROUPS)]
    private ?int $duration = null;

    #[ORM\Column(name: 'lessons_count')]
    #[Assert\NotBlank(message: 'formation.lessons_count.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Type(type: 'integer', message: 'formation.lessons_count.type_integer', groups: self::FORMATION_GROUPS)]
    #[Assert\Positive(message: 'formation.lessons_count.positive', groups: self::FORMATION_GROUPS)]
    private ?int $lessonsCount = null;

    #[ORM\Column(length: 32, enumType: FormationLevel::class)]
    #[Assert\NotNull(message: 'formation.level.not_null', groups: self::FORMATION_GROUPS)]
    #[Assert\Choice(choices: [FormationLevel::BEGINNER, FormationLevel::INTERMEDIATE, FormationLevel::ADVANCED], message: 'formation.level.choice', groups: self::FORMATION_GROUPS)]
    private FormationLevel $level = FormationLevel::BEGINNER;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank(message: 'formation.category.not_blank', groups: self::FORMATION_GROUPS)]
    #[Assert\Choice(callback: [self::class, 'getCategoryKeys'], message: 'formation.category.choice', groups: self::FORMATION_GROUPS)]
    private string $category = self::CATEGORY_DEVELOPMENT;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = trim(strip_tags($description));

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCategoryLabelFr(): string
    {
        return self::CATEGORY_LABELS_FR[$this->category] ?? $this->category;
    }

    public function getLevelLabelFr(): string
    {
        return $this->level?->labelFr() ?? '';
    }

    public function getPriceDisplayFr(): string
    {
        if (null === $this->price) {
            return '—';
        }

        if ($this->price <= 0.0) {
            return 'Gratuit';
        }

        return number_format($this->price, 2, ',', "\u{202f}");
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
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}

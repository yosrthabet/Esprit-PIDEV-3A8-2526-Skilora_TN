<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Formation\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\Table(name: 'formation_quizzes')]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Formation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Formation $formation;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'passing_score')]
    private int $passingScore = 70;

    #[ORM\Column(name: 'time_limit_minutes', nullable: true)]
    private ?int $timeLimitMinutes = null;

    #[ORM\Column(name: 'is_published')]
    private bool $published = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, QuizQuestion> */
    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: QuizQuestion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $questions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->questions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getFormation(): Formation { return $this->formation; }
    public function setFormation(Formation $formation): static { $this->formation = $formation; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPassingScore(): int { return $this->passingScore; }
    public function setPassingScore(int $passingScore): static { $this->passingScore = max(0, min(100, $passingScore)); return $this; }
    public function getTimeLimitMinutes(): ?int { return $this->timeLimitMinutes; }
    public function setTimeLimitMinutes(?int $timeLimitMinutes): static { $this->timeLimitMinutes = $timeLimitMinutes; return $this; }
    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $published): static { $this->published = $published; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    /** @return Collection<int, QuizQuestion> */
    public function getQuestions(): Collection { return $this->questions; }

    public function addQuestion(QuizQuestion $question): static
    {
        if (!$this->questions->contains($question)) {
            $question->setQuiz($this);
            $this->questions->add($question);
        }

        return $this;
    }

    public function getQuestionCount(): int
    {
        return $this->questions->count();
    }
}

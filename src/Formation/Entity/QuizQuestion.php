<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Formation\Repository\QuizQuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizQuestionRepository::class)]
#[ORM\Table(name: 'formation_quiz_questions')]
class QuizQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Quiz $quiz;

    #[ORM\Column(type: Types::TEXT)]
    private string $questionText = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $choices = [];

    #[ORM\Column(name: 'correct_index')]
    private int $correctIndex = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $explanation = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private int $points = 1;

    public function getId(): ?int { return $this->id; }
    public function getQuiz(): Quiz { return $this->quiz; }
    public function setQuiz(Quiz $quiz): static { $this->quiz = $quiz; return $this; }
    public function getQuestionText(): string { return $this->questionText; }
    public function setQuestionText(string $questionText): static { $this->questionText = $questionText; return $this; }
    /** @return list<string> */
    public function getChoices(): array { return $this->choices; }
    /** @param list<string> $choices */
    public function setChoices(array $choices): static { $this->choices = $choices; return $this; }
    public function getCorrectIndex(): int { return $this->correctIndex; }
    public function setCorrectIndex(int $correctIndex): static { $this->correctIndex = $correctIndex; return $this; }
    public function getExplanation(): ?string { return $this->explanation; }
    public function setExplanation(?string $explanation): static { $this->explanation = $explanation; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): static { $this->points = max(1, $points); return $this; }

    public function isCorrect(int $answerIndex): bool
    {
        return $answerIndex === $this->correctIndex;
    }

    public function getCorrectAnswer(): string
    {
        return $this->choices[$this->correctIndex] ?? '';
    }
}

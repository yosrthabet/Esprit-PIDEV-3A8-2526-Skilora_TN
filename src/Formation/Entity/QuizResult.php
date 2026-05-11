<?php

declare(strict_types=1);

namespace App\Formation\Entity;

use App\Entity\User;
use App\Formation\Repository\QuizResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizResultRepository::class)]
#[ORM\Table(name: 'formation_quiz_results')]
class QuizResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Quiz $quiz;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $student;

    #[ORM\Column(name: 'total_points')]
    private int $totalPoints = 0;

    #[ORM\Column(name: 'earned_points')]
    private int $earnedPoints = 0;

    #[ORM\Column(name: 'score_percent', type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $scorePercent = '0.00';

    #[ORM\Column]
    private bool $passed = false;

    /** @var array<int, int> question_id => selected_index */
    #[ORM\Column(type: Types::JSON)]
    private array $answers = [];

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getQuiz(): Quiz { return $this->quiz; }
    public function setQuiz(Quiz $quiz): static { $this->quiz = $quiz; return $this; }
    public function getStudent(): User { return $this->student; }
    public function setStudent(User $student): static { $this->student = $student; return $this; }
    public function getTotalPoints(): int { return $this->totalPoints; }
    public function setTotalPoints(int $totalPoints): static { $this->totalPoints = $totalPoints; return $this; }
    public function getEarnedPoints(): int { return $this->earnedPoints; }
    public function setEarnedPoints(int $earnedPoints): static { $this->earnedPoints = $earnedPoints; return $this; }
    public function getScorePercent(): string { return $this->scorePercent; }
    public function setScorePercent(string $scorePercent): static { $this->scorePercent = $scorePercent; return $this; }
    public function isPassed(): bool { return $this->passed; }
    public function setPassed(bool $passed): static { $this->passed = $passed; return $this; }
    /** @return array<int, int> */
    public function getAnswers(): array { return $this->answers; }
    /** @param array<int, int> $answers */
    public function setAnswers(array $answers): static { $this->answers = $answers; return $this; }
    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }

    public function complete(int $earnedPoints, int $totalPoints, int $passingScore): void
    {
        $this->completedAt = new \DateTimeImmutable();
        $this->earnedPoints = $earnedPoints;
        $this->totalPoints = $totalPoints;
        $this->scorePercent = $totalPoints > 0 ? number_format(($earnedPoints / $totalPoints) * 100, 2, '.', '') : '0.00';
        $this->passed = (float) $this->scorePercent >= $passingScore;
    }
}

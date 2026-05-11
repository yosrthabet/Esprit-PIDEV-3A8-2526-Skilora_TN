<?php

declare(strict_types=1);

namespace App\Formation\Repository;

use App\Entity\User;
use App\Formation\Entity\Formation;
use App\Formation\Entity\Quiz;
use App\Formation\Entity\QuizResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<QuizResult> */
class QuizResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizResult::class);
    }

    public function findLatestForStudentAndQuiz(User $student, Quiz $quiz): ?QuizResult
    {
        /** @var QuizResult|null $result */
        $result = $this->createQueryBuilder('r')
            ->where('r.student = :student')
            ->andWhere('r.quiz = :quiz')
            ->setParameter('student', $student)
            ->setParameter('quiz', $quiz)
            ->orderBy('r.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    /** @return list<QuizResult> */
    public function findForStudent(User $student): array
    {
        /** @var list<QuizResult> $result */
        $result = $this->createQueryBuilder('r')
            ->where('r.student = :student')
            ->setParameter('student', $student)
            ->orderBy('r.completedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /** @return list<QuizResult> */
    public function findByUserAndFormation(User $user, Formation $formation): array
    {
        /** @var list<QuizResult> $result */
        $result = $this->createQueryBuilder('r')
            ->join('r.quiz', 'q')
            ->where('r.student = :user')
            ->andWhere('q.formation = :formation')
            ->setParameter('user', $user)
            ->setParameter('formation', $formation)
            ->orderBy('r.completedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}

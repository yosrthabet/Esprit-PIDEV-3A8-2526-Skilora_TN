<?php

declare(strict_types=1);

namespace App\Formation\Controller;

use App\Controller\AppController;
use App\Formation\Entity\Formation;
use App\Formation\Entity\Quiz;
use App\Formation\Entity\QuizQuestion;
use App\Formation\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class QuizAdminController extends AppController
{
    public function __construct(
        private readonly QuizRepository $quizRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/formations/{id}/quizzes/manage', name: 'app_formation_quiz_manage', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function manage(Formation $formation): Response
    {
        $this->assertTrainerOrAdmin($formation);

        return $this->render('formation/quiz/manage.html.twig', [
            'formation' => $formation,
            'quizzes' => $this->quizRepository->findForFormation($formation),
        ]);
    }

    #[Route('/formations/{id}/quizzes/create', name: 'app_formation_quiz_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function create(Request $request, Formation $formation): Response
    {
        $this->assertTrainerOrAdmin($formation);
        if (!$this->isCsrfTokenValid('quiz_create_' . $formation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $quiz = (new Quiz())
            ->setFormation($formation)
            ->setTitle(trim($request->request->getString('title')) ?: 'Untitled Quiz')
            ->setDescription($request->request->getString('description') ?: null)
            ->setPassingScore($request->request->getInt('passing_score', 70))
            ->setTimeLimitMinutes($request->request->getInt('time_limit') > 0 ? $request->request->getInt('time_limit') : null);
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_formation_quiz_edit', ['id' => $quiz->getId()]);
    }

    #[Route('/quizzes/{id}/edit', name: 'app_formation_quiz_edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(Quiz $quiz): Response
    {
        $this->assertTrainerOrAdmin($quiz->getFormation());

        return $this->render('formation/quiz/edit.html.twig', [
            'quiz' => $quiz,
            'formation' => $quiz->getFormation(),
        ]);
    }

    #[Route('/quizzes/{id}/update', name: 'app_formation_quiz_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Quiz $quiz): Response
    {
        $this->assertTrainerOrAdmin($quiz->getFormation());
        if (!$this->isCsrfTokenValid('quiz_update_' . $quiz->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $quiz->setTitle(trim($request->request->getString('title')) ?: $quiz->getTitle())
            ->setDescription($request->request->getString('description') ?: null)
            ->setPassingScore($request->request->getInt('passing_score', 70))
            ->setTimeLimitMinutes($request->request->getInt('time_limit') > 0 ? $request->request->getInt('time_limit') : null);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_formation_quiz_edit', ['id' => $quiz->getId()]);
    }

    #[Route('/quizzes/{id}/publish', name: 'app_formation_quiz_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(Request $request, Quiz $quiz): Response
    {
        $this->assertTrainerOrAdmin($quiz->getFormation());
        if (!$this->isCsrfTokenValid('quiz_publish_' . $quiz->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $quiz->setPublished(!$quiz->isPublished());
        $this->entityManager->flush();

        return $this->redirectToRoute('app_formation_quiz_manage', ['id' => $quiz->getFormation()->getId()]);
    }

    #[Route('/quizzes/{id}/delete', name: 'app_formation_quiz_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Quiz $quiz): Response
    {
        $formation = $quiz->getFormation();
        $this->assertTrainerOrAdmin($formation);
        if (!$this->isCsrfTokenValid('quiz_delete_' . $quiz->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_formation_quiz_manage', ['id' => $formation->getId()]);
    }

    #[Route('/quizzes/{id}/questions/add', name: 'app_quiz_question_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addQuestion(Request $request, Quiz $quiz): Response
    {
        $this->assertTrainerOrAdmin($quiz->getFormation());
        if (!$this->isCsrfTokenValid('quiz_question_add_' . $quiz->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $choicesRaw = $request->request->getString('choices');
        $choices = array_values(array_filter(array_map('trim', explode("\n", $choicesRaw))));
        $question = (new QuizQuestion())
            ->setQuiz($quiz)
            ->setQuestionText(trim($request->request->getString('question_text')))
            ->setChoices($choices)
            ->setCorrectIndex($request->request->getInt('correct_index', 0))
            ->setExplanation($request->request->getString('explanation') ?: null)
            ->setPosition($quiz->getQuestionCount())
            ->setPoints($request->request->getInt('points', 1));
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_formation_quiz_edit', ['id' => $quiz->getId()]);
    }

    #[Route('/quizzes/{quizId}/questions/{id}/delete', name: 'app_quiz_question_delete', methods: ['POST'], requirements: ['quizId' => '\d+', 'id' => '\d+'])]
    public function deleteQuestion(Request $request, int $quizId, QuizQuestion $question): Response
    {
        $quiz = $question->getQuiz();
        $this->assertTrainerOrAdmin($quiz->getFormation());
        if (!$this->isCsrfTokenValid('quiz_question_delete_' . $question->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $this->entityManager->remove($question);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_formation_quiz_edit', ['id' => $quiz->getId()]);
    }

    private function assertTrainerOrAdmin(Formation $formation): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        $user = $this->getAppUser();
        if ($formation->getTrainer()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Only the trainer or admin can manage quizzes.');
        }
    }
}

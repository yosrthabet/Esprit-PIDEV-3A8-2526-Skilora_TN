<?php

declare(strict_types=1);

namespace App\Controller\Ml;

use App\Controller\AppController;
use App\Formation\Repository\EnrollmentRepository;
use App\Formation\Repository\FormationRepository;
use App\Formation\Repository\QuizResultRepository;
use App\Formation\Service\FormationProgressService;
use App\Service\AI\SkiloraMlClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formation/ml')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FormationMlController extends AppController
{
    public function __construct(
        private readonly SkiloraMlClient $mlClient,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly FormationRepository $formationRepository,
        private readonly QuizResultRepository $quizResultRepository,
        private readonly FormationProgressService $progressService,
    ) {
    }

    #[Route('', name: 'app_formation_ml', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('formation/ml/index.html.twig');
    }

    #[Route('/recommend', name: 'app_formation_ml_recommend', methods: ['POST'])]
    public function recommend(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];

        $skills = $data['user_skills']
            ?? array_filter(array_map('trim', explode(',', $request->request->getString('skills'))));
        $completedCourses = $data['completed_courses'] ?? [];
        $careerGoal = $data['career_goal'] ?? $request->request->getString('career_goal');
        $level = $data['experience_level'] ?? $request->request->getString('experience_level', 'beginner');
        $interests = $data['interests'] ?? [];

        return $this->json($this->mlClient->recommendFormations([
            'user_skills' => $skills,
            'completed_courses' => $completedCourses,
            'interests' => $interests,
            'career_goal' => $careerGoal,
            'experience_level' => $level,
        ]));
    }

    #[Route('/completion-predict', name: 'app_formation_ml_completion_predict', methods: ['POST'])]
    public function completionPredict(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $enrollmentId = (int) ($data['enrollment_id'] ?? $request->request->getInt('enrollment_id'));

        $user = $this->getAppUser();
        $enrollment = $this->enrollmentRepository->find($enrollmentId);

        if ($enrollment === null || $enrollment->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Enrollment not found'], 404);
        }

        $formation = $enrollment->getFormation();
        $totalModules = $formation->getModules()->count();
        $completedModules = 0;
        foreach ($enrollment->getLessonProgress() as $lp) {
            if ($lp->isComplete()) {
                ++$completedModules;
            }
        }

        $quizScores = [];
        $quizResults = $this->quizResultRepository->findByUserAndFormation($user, $formation);
        foreach ($quizResults as $result) {
            $quizScores[] = (float) $result->getScorePercent();
        }

        $daysEnrolled = max(1, (int) $enrollment->getCreatedAt()->diff(new \DateTimeImmutable())->days);

        return $this->json($this->mlClient->predictCompletion([
            'user_id' => $user->getId(),
            'formation_id' => $formation->getId(),
            'progress_percent' => $this->progressService->getCompletionPercent($enrollment),
            'days_enrolled' => $daysEnrolled,
            'quiz_scores' => $quizScores,
            'modules_completed' => $completedModules,
            'total_modules' => max(1, $totalModules),
            'avg_time_per_module' => 30,
            'login_frequency' => 3.0,
        ]));
    }

    #[Route('/personalized', name: 'app_formation_ml_personalized', methods: ['POST'])]
    public function personalized(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];

        $completedCourses = [];
        foreach ($this->enrollmentRepository->findForUser($this->getAppUser()) as $enrollment) {
            if ($enrollment->getCertificate() !== null) {
                $completedCourses[] = $enrollment->getFormation()->getTitle();
            }
        }

        $formations = $this->formationRepository->findPublished(null, 30);
        $catalog = [];
        foreach ($formations as $f) {
            $reviews = $f->getReviews();
            $avgRating = 0.0;
            if ($reviews->count() > 0) {
                $sum = 0;
                foreach ($reviews as $r) {
                    $sum += $r->getRating();
                }
                $avgRating = round($sum / $reviews->count(), 1);
            }
            $catalog[] = [
                'id' => $f->getId(),
                'title' => $f->getTitle(),
                'level' => $f->getLevel()?->value ?? 'beginner',
                'category' => $f->getCategory() ?? 'general',
                'rating' => $avgRating,
                'price' => $f->getPriceAmount() ?? 'free',
            ];
        }

        return $this->json($this->mlClient->personalizedRecommendations([
            'user_skills' => $data['user_skills'] ?? [],
            'completed_courses' => $completedCourses,
            'career_goal' => $data['career_goal'] ?? '',
            'experience_level' => $data['experience_level'] ?? 'beginner',
            'available_formations' => $catalog,
        ]));
    }
}

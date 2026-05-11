<?php

declare(strict_types=1);

namespace App\Controller\Ml;

use App\Controller\AppController;
use App\Service\AI\SkiloraMlClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/recruitment/ml')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class RecruitmentMlController extends AppController
{
    public function __construct(private readonly SkiloraMlClient $mlClient)
    {
    }

    #[Route('', name: 'app_recruitment_ml', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('recruitment/ml/index.html.twig');
    }

    #[Route('/match', name: 'app_recruitment_ml_match', methods: ['POST'])]
    public function match(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->matchSkills([
            'skills' => array_filter(explode(',', $request->request->getString('skills'))),
            'job_skills' => array_filter(explode(',', $request->request->getString('job_skills'))),
        ]));
    }

    #[Route('/semantic-match', name: 'app_recruitment_ml_semantic_match', methods: ['POST'])]
    public function semanticMatch(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->semanticMatch([
            'cv_text' => $request->request->getString('cv_text'),
            'job_description' => $request->request->getString('job_description'),
        ]));
    }

    #[Route('/analyze-cv', name: 'app_recruitment_ml_analyze_cv', methods: ['POST'])]
    public function analyzeCv(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->analyzeCv([
            'cv_text' => $request->request->getString('cv_text'),
        ]));
    }

    #[Route('/salary-predict', name: 'app_recruitment_ml_salary_predict', methods: ['POST'])]
    public function salaryPredict(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->predictSalary([
            'title' => $request->request->getString('title'),
            'skills' => array_filter(explode(',', $request->request->getString('skills'))),
            'experience_years' => $request->request->getInt('experience_years'),
            'location' => $request->request->getString('location'),
        ]));
    }

    #[Route('/interview-questions', name: 'app_recruitment_ml_interview_questions', methods: ['POST'])]
    public function interviewQuestions(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->generateInterviewQuestions([
            'job_title' => $request->request->getString('job_title'),
            'job_description' => $request->request->getString('job_description'),
            'count' => $request->request->getInt('count', 5),
        ]));
    }
}

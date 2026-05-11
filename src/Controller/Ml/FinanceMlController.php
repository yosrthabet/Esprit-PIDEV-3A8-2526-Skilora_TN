<?php

declare(strict_types=1);

namespace App\Controller\Ml;

use App\Controller\AppController;
use App\Service\AI\SkiloraMlClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/finance/ml')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FinanceMlController extends AppController
{
    public function __construct(private readonly SkiloraMlClient $mlClient)
    {
    }

    #[Route('/anomaly-detect', name: 'app_finance_ml_anomaly', methods: ['POST'])]
    public function anomalyDetect(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->detectAnomalies([
            'user_id' => $this->getAppUser()->getId() ?? 0,
        ]));
    }

    #[Route('/spending-analysis', name: 'app_finance_ml_spending', methods: ['POST'])]
    public function spendingAnalysis(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->analyzeSpending([
            'user_id' => $this->getAppUser()->getId() ?? 0,
        ]));
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Ml;

use App\Controller\AppController;
use App\Service\AI\SkiloraMlClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/community/ml')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CommunityMlController extends AppController
{
    public function __construct(private readonly SkiloraMlClient $mlClient)
    {
    }

    #[Route('/moderate', name: 'app_community_ml_moderate', methods: ['POST'])]
    public function moderate(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->moderateContent([
            'content' => $request->request->getString('content'),
        ]));
    }

    #[Route('/sentiment', name: 'app_community_ml_sentiment', methods: ['POST'])]
    public function sentiment(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->analyzeSentiment([
            'content' => $request->request->getString('content'),
        ]));
    }

    #[Route('/translate', name: 'app_community_ml_translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->translate([
            'content' => $request->request->getString('content'),
            'target_lang' => $request->request->getString('target_lang', 'en'),
        ]));
    }
}

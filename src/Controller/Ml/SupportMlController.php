<?php

declare(strict_types=1);

namespace App\Controller\Ml;

use App\Controller\AppController;
use App\Service\AI\SkiloraMlClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support/ml')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SupportMlController extends AppController
{
    public function __construct(private readonly SkiloraMlClient $mlClient)
    {
    }

    #[Route('/triage', name: 'app_support_ml_triage', methods: ['POST'])]
    public function triage(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->triageTicket([
            'subject' => $request->request->getString('subject'),
            'description' => $request->request->getString('description'),
        ]));
    }

    #[Route('/smart-reply', name: 'app_support_ml_smart_reply', methods: ['POST'])]
    public function smartReply(Request $request): JsonResponse
    {
        return $this->json($this->mlClient->smartReply([
            'subject' => $request->request->getString('subject'),
            'description' => $request->request->getString('description'),
        ]));
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Formation;

use App\Service\ChatbotServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotServiceInterface $chatbotService,
    ) {
    }

    #[Route('/formations/chatbot/message', name: 'app_formations_chatbot_message', methods: ['POST'])]
    #[Route('/chatbot/ask', name: 'app_chatbot_ask', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function message(Request $request): JsonResponse
    {
        if (!$this->isAuthorizedFormationChatOrigin($request)) {
            return new JsonResponse(['error' => 'Unauthorized chatbot context.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid payload.'], Response::HTTP_BAD_REQUEST);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        $context = $this->normalizeContext(\is_array($payload['context'] ?? null) ? $payload['context'] : []);

        $answer = $this->chatbotService->answer($message, $context);

        $visible = \is_array($context['visible_formations'] ?? null) ? $context['visible_formations'] : [];

        return new JsonResponse(array_merge($answer->toArray(), [
            'contextEcho' => [
                'active_category' => $context['active_category'],
                'search_query' => $context['search_query'],
                'filter_level' => $context['filter_level'],
                'visible_count' => \is_array($visible) ? count($visible) : 0,
            ],
        ]));
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function normalizeContext(array $raw): array
    {
        return [
            'search_query' => $this->stringOrEmpty($raw['search_query'] ?? null),
            'active_category' => $this->stringOrEmpty($raw['active_category'] ?? null),
            'filter_level' => $this->stringOrEmpty($raw['filter_level'] ?? null),
            'visible_formations' => \is_array($raw['visible_formations'] ?? null) ? $raw['visible_formations'] : [],
        ];
    }

    private function stringOrEmpty(mixed $v): string
    {
        if (!\is_string($v)) {
            return '';
        }

        return trim($v);
    }

    private function isAuthorizedFormationChatOrigin(Request $request): bool
    {
        $originPath = (string) $request->headers->get('X-Chat-Origin-Path', '');
        if ('' === $originPath) {
            return false;
        }

        $allowedPrefixes = ['/formations', '/my-formations', '/my-certificates', '/certificate', '/certificates'];
        $matches = false;
        foreach ($allowedPrefixes as $prefix) {
            if ($originPath === $prefix || str_starts_with($originPath, $prefix.'/')) {
                $matches = true;
                break;
            }
        }
        if (!$matches) {
            return false;
        }

        $referer = (string) $request->headers->get('Referer', '');
        if ('' === $referer) {
            return false;
        }

        $refererPath = parse_url($referer, PHP_URL_PATH);
        if (!\is_string($refererPath)) {
            return false;
        }

        foreach ($allowedPrefixes as $prefix) {
            if ($refererPath === $prefix || str_starts_with($refererPath, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}

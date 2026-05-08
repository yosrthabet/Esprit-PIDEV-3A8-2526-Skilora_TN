<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\User;
use Symfony\Component\Routing\RouterInterface;

final class NotificationUrlResolver
{
    private const FALLBACK_ROUTE = 'app_notifications_index';

    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function resolve(?string $referenceType, ?int $referenceId, ?User $user = null): string
    {
        if ($referenceType === null || $referenceId === null) {
            return $this->fallbackUrl();
        }

        foreach ($this->candidateRoutes($referenceType, $user) as $routeName) {
            if ($this->routeExists($routeName)) {
                return $this->router->generate($routeName, ['id' => $referenceId]);
            }
        }

        return $this->fallbackUrl();
    }

    /** @return list<string> */
    private function candidateRoutes(string $referenceType, ?User $user): array
    {
        $role = strtoupper((string) $user?->getRole());

        $routes = match ($referenceType) {
            'application' => ['app_application_show'],
            'support_ticket' => $role === 'ADMIN'
                ? ['app_admin_support_show', 'app_support_show']
                : ['app_support_show'],
            'contract' => ['app_contract_show'],
            'formation' => match ($role) {
                'ADMIN' => ['app_admin_formation_show', 'app_formation_show'],
                'TRAINER' => ['app_trainer_formation_show', 'app_formation_show'],
                default => ['app_formation_show'],
            },
            'certificate' => match ($role) {
                'ADMIN' => ['app_admin_certificate_show', 'app_certificate_show'],
                'TRAINER' => ['app_trainer_certificate_show', 'app_certificate_show'],
                default => ['app_certificate_show'],
            },
            'community_post' => match ($role) {
                'ADMIN' => ['app_community_post_show', 'app_admin_community'],
                default => ['app_community_post_show'],
            },
            'dm_conversation' => ['app_inbox_conversation', 'app_inbox'],
            default => [],
        };

        return array_values(array_unique($routes));
    }

    private function routeExists(string $routeName): bool
    {
        return $this->router->getRouteCollection()->get($routeName) !== null;
    }

    private function fallbackUrl(): string
    {
        if (!$this->routeExists(self::FALLBACK_ROUTE)) {
            return '/notifications';
        }

        return $this->router->generate(self::FALLBACK_ROUTE);
    }
}

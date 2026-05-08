<?php

namespace App\Service\User;

use App\Entity\User;
use Symfony\Component\Routing\RouterInterface;

final class WorkspaceRedirectService
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function getHubUrl(): string
    {
        return $this->router->generate('app_workspace');
    }

    public function getUrlForUser(User $user): string
    {
        return match (strtoupper((string) $user->getRole())) {
            'ADMIN'   => $this->router->generate('app_dashboard'),
            'TRAINER' => $this->router->generate('app_trainer_dashboard'),
            default   => $this->router->generate('app_workspace'),
        };
    }
}

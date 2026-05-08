<?php

declare(strict_types=1);

namespace App\Tests\Service\User;

use App\Entity\User;
use App\Service\User\WorkspaceRedirectService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class WorkspaceRedirectServiceTest extends TestCase
{
    public function testGetHubUrlReturnsWorkspaceRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_workspace')
            ->willReturn('/workspace');

        $service = new WorkspaceRedirectService($router);
        $result = $service->getHubUrl();

        $this->assertSame('/workspace', $result);
    }

    public function testGetUrlForAdminUserReturnsDashboard(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_dashboard')
            ->willReturn('/admin/dashboard');

        $user = new User();
        $user->setRole('ADMIN');

        $service = new WorkspaceRedirectService($router);
        $result = $service->getUrlForUser($user);

        $this->assertSame('/admin/dashboard', $result);
    }

    public function testGetUrlForEmployerUserReturnsWorkspace(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_workspace')
            ->willReturn('/workspace');

        $user = new User();
        $user->setRole('EMPLOYER');

        $service = new WorkspaceRedirectService($router);
        $result = $service->getUrlForUser($user);

        $this->assertSame('/workspace', $result);
    }

    public function testGetUrlForTrainerUserReturnsTrainerDashboard(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_trainer_dashboard')
            ->willReturn('/trainer/dashboard');

        $user = new User();
        $user->setRole('TRAINER');

        $service = new WorkspaceRedirectService($router);
        $result = $service->getUrlForUser($user);

        $this->assertSame('/trainer/dashboard', $result);
    }

    public function testGetUrlForFreelancerUserReturnsWorkspace(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_workspace')
            ->willReturn('/workspace');

        $user = new User();
        $user->setRole('FREELANCER');

        $service = new WorkspaceRedirectService($router);
        $result = $service->getUrlForUser($user);

        $this->assertSame('/workspace', $result);
    }

    public function testGetUrlHandlesCaseInsensitiveRole(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_dashboard')
            ->willReturn('/admin/dashboard');

        $user = new User();
        $user->setRole('admin');

        $service = new WorkspaceRedirectService($router);
        $result = $service->getUrlForUser($user);

        $this->assertSame('/admin/dashboard', $result);
    }
}

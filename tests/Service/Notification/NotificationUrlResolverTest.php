<?php

declare(strict_types=1);

namespace App\Tests\Service\Notification;

use App\Entity\User;
use App\Service\Notification\NotificationUrlResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class NotificationUrlResolverTest extends TestCase
{
    public function testResolveReturnsApplicationUrlWhenRouteExists(): void
    {
        $router = $this->routerWithRoutes(['app_notifications_index', 'app_application_show']);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_application_show', ['id' => 42])
            ->willReturn('/applications/42');

        $resolver = new NotificationUrlResolver($router);

        $this->assertSame('/applications/42', $resolver->resolve('application', 42));
    }

    public function testResolveFallsBackWhenFormationRouteDoesNotExist(): void
    {
        $router = $this->routerWithRoutes(['app_notifications_index']);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_notifications_index')
            ->willReturn('/notifications');

        $resolver = new NotificationUrlResolver($router);

        $this->assertSame('/notifications', $resolver->resolve('formation', 10));
    }

    public function testResolveUsesAdminSupportRouteForAdminUsers(): void
    {
        $router = $this->routerWithRoutes(['app_notifications_index', 'app_admin_support_show', 'app_support_show']);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_admin_support_show', ['id' => 7])
            ->willReturn('/admin/support/7');

        $user = (new User())->setRole('ADMIN');
        $resolver = new NotificationUrlResolver($router);

        $this->assertSame('/admin/support/7', $resolver->resolve('support_ticket', 7, $user));
    }

    public function testResolveSupportsCertificateReferencesWhenRouteExists(): void
    {
        $router = $this->routerWithRoutes(['app_notifications_index', 'app_certificate_show']);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_certificate_show', ['id' => 5])
            ->willReturn('/certificates/5');

        $resolver = new NotificationUrlResolver($router);

        $this->assertSame('/certificates/5', $resolver->resolve('certificate', 5));
    }

    public function testResolveSupportsCommunityPostReferencesWhenRouteExists(): void
    {
        $router = $this->routerWithRoutes(['app_notifications_index', 'app_community_post_show']);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_community_post_show', ['id' => 9])
            ->willReturn('/community/posts/9');

        $resolver = new NotificationUrlResolver($router);

        $this->assertSame('/community/posts/9', $resolver->resolve('community_post', 9));
    }

    public function testResolveSupportsDmConversationReferencesWhenRouteExists(): void
    {
        $router = $this->routerWithRoutes(['app_notifications_index', 'app_inbox_conversation']);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_inbox_conversation', ['id' => 11])
            ->willReturn('/inbox/11');

        $resolver = new NotificationUrlResolver($router);

        $this->assertSame('/inbox/11', $resolver->resolve('dm_conversation', 11));
    }

    public function testResolveSupportsContractReferencesWhenRouteExists(): void
    {
        $router = $this->routerWithRoutes(['app_notifications_index', 'app_contract_show']);
        $router->expects($this->once())
            ->method('generate')
            ->with('app_contract_show', ['id' => 15])
            ->willReturn('/contracts/15');

        $resolver = new NotificationUrlResolver($router);

        $this->assertSame('/contracts/15', $resolver->resolve('contract', 15));
    }

    /** @param list<string> $routeNames */
    private function routerWithRoutes(array $routeNames): MockObject&RouterInterface
    {
        $routes = new RouteCollection();
        foreach ($routeNames as $routeName) {
            $routes->add($routeName, new Route('/' . $routeName));
        }

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);

        return $router;
    }
}

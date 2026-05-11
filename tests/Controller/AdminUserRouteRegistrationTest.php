<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AdminUserRouteRegistrationTest extends KernelTestCase
{
    /**
     * @dataProvider routeNames
     */
    public function testAdminUserRouteExists(string $routeName): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        self::assertNotNull($router->getRouteCollection()->get($routeName), sprintf('Route %s should exist.', $routeName));
    }

    public static function routeNames(): iterable
    {
        yield 'index' => ['app_admin_user_index'];
        yield 'legacy-index' => ['app_admin_user_index_legacy'];
        yield 'edit' => ['app_admin_user_edit'];
        yield 'legacy-edit' => ['app_admin_user_edit_legacy'];
        yield 'toggle-active' => ['app_admin_user_toggle_active'];
    }
}

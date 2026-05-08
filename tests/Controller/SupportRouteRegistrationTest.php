<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class SupportRouteRegistrationTest extends KernelTestCase
{
    /**
     * @dataProvider routeNames
     */
    public function testSupportRouteExists(string $routeName): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        self::assertNotNull($router->getRouteCollection()->get($routeName), sprintf('Route %s should exist.', $routeName));
    }

    public static function routeNames(): iterable
    {
        yield 'support-index' => ['app_support'];
        yield 'support-legacy-index' => ['app_support_legacy'];
        yield 'support-new' => ['app_support_new'];
        yield 'support-show' => ['app_support_show'];
        yield 'support-messages' => ['app_support_messages'];
        yield 'support-export-pdf' => ['app_support_export_pdf'];
        yield 'admin-support-index' => ['app_admin_support'];
        yield 'admin-support-export-csv' => ['app_admin_support_export_csv'];
        yield 'admin-support-show' => ['app_admin_support_show'];
        yield 'admin-support-messages' => ['app_admin_support_messages'];
        yield 'admin-support-status' => ['app_admin_support_status'];
        yield 'admin-support-export-pdf' => ['app_admin_support_export_pdf'];
    }
}

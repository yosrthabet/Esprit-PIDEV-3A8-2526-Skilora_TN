<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class MessagingRouteRegistrationTest extends KernelTestCase
{
    /**
     * @dataProvider routeNames
     */
    public function testMessagingRouteExists(string $routeName): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        self::assertNotNull($router->getRouteCollection()->get($routeName), sprintf('Route %s should exist.', $routeName));
    }

    public static function routeNames(): iterable
    {
        yield 'inbox' => ['app_inbox'];
        yield 'conversation' => ['app_inbox_conversation'];
        yield 'start' => ['app_inbox_start'];
        yield 'messages' => ['app_inbox_messages'];
        yield 'send' => ['app_inbox_send'];
        yield 'unread-count' => ['app_inbox_unread_count'];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class CommunityRouteRegistrationTest extends KernelTestCase
{
    /**
     * @dataProvider routeNames
     */
    public function testCommunityRouteExists(string $routeName): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        self::assertNotNull($router->getRouteCollection()->get($routeName), sprintf('Route %s should exist.', $routeName));
    }

    public static function routeNames(): iterable
    {
        yield 'feed' => ['app_community'];
        yield 'new-post' => ['app_community_post_new'];
        yield 'show-post' => ['app_community_post_show'];
        yield 'comment' => ['app_community_post_comment'];
        yield 'like' => ['app_community_post_like'];
        yield 'delete' => ['app_community_post_delete'];
        yield 'admin' => ['app_admin_community'];
        yield 'admin-approve' => ['app_admin_community_post_approve'];
        yield 'admin-reject' => ['app_admin_community_post_reject'];
        yield 'groups' => ['app_community_groups'];
        yield 'group-new' => ['app_community_group_new'];
        yield 'group-show' => ['app_community_group_show'];
        yield 'group-join' => ['app_community_group_join'];
        yield 'events' => ['app_community_events'];
        yield 'event-new' => ['app_community_event_new'];
        yield 'event-show' => ['app_community_event_show'];
        yield 'event-rsvp' => ['app_community_event_rsvp'];
        yield 'blog' => ['app_community_blog'];
        yield 'blog-new' => ['app_community_blog_new'];
        yield 'blog-show' => ['app_community_blog_show'];
    }
}

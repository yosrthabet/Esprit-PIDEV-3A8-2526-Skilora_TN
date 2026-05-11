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

    /** @return iterable<string, array{string}> */
    public static function routeNames(): iterable
    {
        yield 'feed' => ['app_community'];
        yield 'for-you' => ['app_community_for_you'];
        yield 'following' => ['app_community_following'];
        yield 'saved' => ['app_community_saved'];
        yield 'new-post' => ['app_community_post_new'];
        yield 'show-post' => ['app_community_post_show'];
        yield 'comment' => ['app_community_post_comment'];
        yield 'like' => ['app_community_post_like'];
        yield 'react' => ['app_community_post_react'];
        yield 'bookmark' => ['app_community_post_bookmark'];
        yield 'share' => ['app_community_post_share'];
        yield 'report' => ['app_community_post_report'];
        yield 'tone-check' => ['app_community_ai_tone_check'];
        yield 'ai-improve' => ['app_community_ai_improve'];
        yield 'delete' => ['app_community_post_delete'];
        yield 'admin' => ['app_admin_community'];
        yield 'admin-approve' => ['app_admin_community_post_approve'];
        yield 'admin-reject' => ['app_admin_community_post_reject'];
        yield 'groups' => ['app_community_groups'];
        yield 'group-new' => ['app_community_group_new'];
        yield 'group-show' => ['app_community_group_show'];
        yield 'group-join' => ['app_community_group_join'];
        yield 'group-post' => ['app_community_group_post'];
        yield 'events' => ['app_community_events'];
        yield 'event-new' => ['app_community_event_new'];
        yield 'event-show' => ['app_community_event_show'];
        yield 'event-rsvp' => ['app_community_event_rsvp'];
        yield 'blog' => ['app_community_blog'];
        yield 'blog-new' => ['app_community_blog_new'];
        yield 'blog-show' => ['app_community_blog_show'];
        yield 'search' => ['app_community_search'];
        yield 'search-api' => ['app_community_search_api'];
        yield 'mentions' => ['app_community_mentions'];
        yield 'edit-post' => ['app_community_post_edit'];
        yield 'group-edit' => ['app_community_group_edit'];
        yield 'group-delete' => ['app_community_group_delete'];
        yield 'group-leave' => ['app_community_group_leave'];
        yield 'network' => ['app_community_network'];
        yield 'invitation-send' => ['app_community_invitation_send'];
        yield 'invitation-accept' => ['app_community_invitation_accept'];
        yield 'invitation-decline' => ['app_community_invitation_decline'];
        yield 'invitation-cancel' => ['app_community_invitation_cancel'];
    }
}

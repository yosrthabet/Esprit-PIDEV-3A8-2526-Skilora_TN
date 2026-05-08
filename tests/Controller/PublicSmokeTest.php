<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests for all public (unauthenticated) pages.
 * Verifies no 500 errors, DB issues, or broken templates.
 */
class PublicSmokeTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @dataProvider publicGetRoutes
     */
    public function testPublicPageLoads(string $url, int $expectedStatus): void
    {
        $this->client->request('GET', $url);
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, "500 Internal Server Error on GET $url");
        $this->assertEquals($expectedStatus, $status, "Unexpected status $status on GET $url");
    }

    public static function publicGetRoutes(): iterable
    {
        // Home
        yield 'home' => ['/', 200];

        // Auth pages
        yield 'login' => ['/login', 200];
        yield 'register' => ['/register', 200];
        yield 'forgot-password' => ['/forgot-password', 200];

        // Public marketing pages
        yield 'about' => ['/about', 200];
        yield 'pricing' => ['/pricing', 200];
        yield 'careers' => ['/careers', 200];
        yield 'health' => ['/health', 200];
        yield 'certificate-verify-invalid' => ['/certificate/verify/not-a-real-certificate', 200];
        yield 'formations' => ['/formations', 200];

        // Redirect/placeholder pages (redirect when unauthenticated)
        yield 'recruitment' => ['/recruitment', 302];
        yield 'finance' => ['/finance', 302];
        yield 'admin-finance' => ['/admin/finance', 302];
        yield 'jobs' => ['/jobs', 302];
        yield 'community' => ['/community', 302];
        yield 'community-groups' => ['/community/groups', 302];
        yield 'community-events' => ['/community/events', 302];
        yield 'community-blog' => ['/community/blog', 302];

        // OAuth complete-registration (no session → should redirect to /register)
        yield 'oauth-complete-no-session' => ['/oauth/complete-registration', 302];
    }

    /**
     * Protected pages should redirect to login (302) when unauthenticated.
     *
     * @dataProvider protectedGetRoutes
     */
    public function testProtectedPageRedirectsToLogin(string $url): void
    {
        $this->client->request('GET', $url);
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, "500 Internal Server Error on GET $url");
        $this->assertContains(
            $status,
            [301, 302, 303],
            "Expected redirect on protected route GET $url, got $status"
        );
    }

    public static function protectedGetRoutes(): iterable
    {
        yield 'dashboard' => ['/dashboard'];
        yield 'workspace' => ['/workspace'];
        yield 'settings' => ['/settings'];
        yield 'profile' => ['/profile'];
        yield 'support' => ['/support-space'];
        yield 'notifications' => ['/notifications'];
        yield 'inbox' => ['/inbox'];
        yield 'inbox-unread-count' => ['/api/inbox/unread-count'];
        yield 'applications' => ['/applications'];
        yield 'job-preferences' => ['/job-preferences'];
        yield 'interviews' => ['/interviews'];
        yield 'learning' => ['/learning'];
        yield 'certificates' => ['/certificates'];
    }
}

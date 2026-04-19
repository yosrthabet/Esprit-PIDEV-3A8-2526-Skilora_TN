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

        // Public listings
        yield 'formations' => ['/formations', 200];
        yield 'offres' => ['/offres', 302];

        // Redirect/placeholder pages
        yield 'recruitment' => ['/recruitment', 302];
        yield 'finance' => ['/finance', 302];
        yield 'community' => ['/community', 302];
        yield 'jobs' => ['/jobs', 302];

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
        // Dashboard
        yield 'dashboard' => ['/dashboard'];
        yield 'workspace' => ['/workspace'];

        // Settings
        yield 'settings' => ['/settings'];

        // Profile
        yield 'profile' => ['/profile'];

        // User formations
        yield 'my-formations' => ['/my-formations'];
        yield 'my-certificates' => ['/my-certificates'];

        // Support
        yield 'support' => ['/support'];
        yield 'support-new' => ['/support/new'];

        // Community (authenticated)
        yield 'community-posts' => ['/community/posts'];
        yield 'community-network' => ['/community/reseau'];

        // Candidate area
        yield 'candidate-applications' => ['/mon-espace/candidatures'];
        yield 'candidate-interviews' => ['/mon-espace/entretiens'];
        yield 'candidate-cv' => ['/mon-espace/cv/generateur'];

        // Admin pages
        yield 'admin-users' => ['/admin/users'];
        yield 'admin-formations' => ['/admin/formations'];
        yield 'admin-certificates' => ['/admin/certificates'];
        yield 'admin-community' => ['/admin/community'];
        yield 'admin-support' => ['/admin/support'];
        yield 'admin-finance' => ['/admin/finance'];
        yield 'admin-recruitment' => ['/admin/recruitment'];
    }
}

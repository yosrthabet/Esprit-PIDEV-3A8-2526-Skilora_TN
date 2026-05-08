<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests for authenticated USER (freelancer) pages.
 * Logs in as a regular USER, hits every user-accessible route.
 */
class UserSmokeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->em->getRepository(User::class)->findOneBy(['role' => 'USER', 'active' => true]);
        if (!$user) {
            $this->markTestSkipped('No active USER in database — cannot run user smoke tests.');
        }
        $this->client->loginUser($user);
    }

    /**
     * @dataProvider userGetRoutes
     */
    public function testUserPageLoads(string $url): void
    {
        $this->client->request('GET', $url);
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, "500 Internal Server Error on GET $url");
        $this->assertLessThan(400, $status, "Error status $status on GET $url");
    }

    public static function userGetRoutes(): iterable
    {
        // Workspace
        yield 'workspace' => ['/workspace'];

        // Profile & Settings
        yield 'profile' => ['/profile'];
        yield 'settings' => ['/settings'];

        // Jobs
        yield 'jobs' => ['/jobs'];
        yield 'job-preferences' => ['/job-preferences'];

        // Learning
        yield 'formations' => ['/formations'];
        yield 'learning' => ['/learning'];
        yield 'certificates' => ['/certificates'];

        // Community
        yield 'community' => ['/community'];
        yield 'community-groups' => ['/community/groups'];
        yield 'community-events' => ['/community/events'];
        yield 'community-blog' => ['/community/blog'];

        // Support
        yield 'support' => ['/support-space'];

        // Public pages (should still work when logged in)
        yield 'home' => ['/'];
    }
}

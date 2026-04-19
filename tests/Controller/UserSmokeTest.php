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

        // Formations
        yield 'formations' => ['/formations'];
        yield 'my-formations' => ['/my-formations'];
        yield 'my-certificates' => ['/my-certificates'];

        // Community
        yield 'community-posts' => ['/community/posts'];
        yield 'community-network' => ['/community/reseau'];

        // Support
        yield 'support' => ['/support'];
        yield 'support-new' => ['/support/new'];

        // Candidate area
        yield 'candidate-applications' => ['/mon-espace/candidatures'];
        yield 'candidate-interviews' => ['/mon-espace/entretiens'];
        yield 'candidate-cv' => ['/mon-espace/cv/generateur'];

        // Public listings (should still work when logged in)
        yield 'offres' => ['/offres'];
        yield 'home' => ['/'];

        // Finance
        yield 'user-finance' => ['/workspace/finance'];
    }
}

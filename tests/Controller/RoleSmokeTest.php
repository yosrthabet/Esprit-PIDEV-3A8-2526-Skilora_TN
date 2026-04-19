<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests for EMPLOYER and TRAINER role pages.
 */
class RoleSmokeTest extends WebTestCase
{
    // ── Employer ────────────────────────────────────────────

    /**
     * @dataProvider employerGetRoutes
     */
    public function testEmployerPageLoads(string $url): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $employer = $em->getRepository(User::class)->findOneBy(['role' => 'EMPLOYER', 'active' => true]);
        if (!$employer) {
            $this->markTestSkipped('No active EMPLOYER in database.');
        }

        $client->loginUser($employer);
        $client->request('GET', $url);
        $status = $client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, "500 Internal Server Error on GET $url (EMPLOYER)");
        $this->assertLessThan(400, $status, "Error status $status on GET $url (EMPLOYER)");
    }

    public static function employerGetRoutes(): iterable
    {
        yield 'employer-dashboard' => ['/employer'];
        yield 'employer-applications' => ['/employer/candidatures'];
        yield 'employer-interviews' => ['/employer/entretiens'];
        yield 'employer-profile' => ['/employer/profil'];
        yield 'employer-finance' => ['/employer/finance'];
        yield 'employer-job-offers' => ['/employer/job-offers'];
        yield 'employer-job-offers-page' => ['/employer/job-offers/page'];
        yield 'employer-job-offers-new' => ['/employer/job-offers/new'];
    }

    // ── Trainer ─────────────────────────────────────────────

    /**
     * @dataProvider trainerGetRoutes
     */
    public function testTrainerPageLoads(string $url): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $trainer = $em->getRepository(User::class)->findOneBy(['role' => 'TRAINER', 'active' => true]);
        if (!$trainer) {
            $this->markTestSkipped('No active TRAINER in database.');
        }

        $client->loginUser($trainer);
        $client->request('GET', $url);
        $status = $client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, "500 Internal Server Error on GET $url (TRAINER)");
        $this->assertLessThan(400, $status, "Error status $status on GET $url (TRAINER)");
    }

    public static function trainerGetRoutes(): iterable
    {
        yield 'trainer-dashboard' => ['/trainer'];
        yield 'trainer-formations' => ['/trainer/formations'];
        yield 'trainer-formations-new' => ['/trainer/formations/new'];
    }
}

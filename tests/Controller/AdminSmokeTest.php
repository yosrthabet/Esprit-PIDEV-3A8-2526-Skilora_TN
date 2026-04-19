<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests for all admin-only pages.
 * Logs in as ADMIN, hits every admin route, asserts no 500 / DB errors.
 */
class AdminSmokeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->em->getRepository(User::class)->findOneBy(['role' => 'ADMIN']);
        if (!$admin) {
            $this->markTestSkipped('No ADMIN user in database — cannot run admin smoke tests.');
        }
        $this->client->loginUser($admin);
    }

    /**
     * @dataProvider adminGetRoutes
     */
    public function testAdminPageLoads(string $url): void
    {
        $this->client->request('GET', $url);
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, "500 Internal Server Error on GET $url");
        $this->assertLessThan(400, $status, "Error status $status on GET $url");
    }

    public static function adminGetRoutes(): iterable
    {
        // Dashboard
        yield 'dashboard' => ['/dashboard'];

        // User management
        yield 'admin-users' => ['/admin/users'];
        yield 'admin-users-list' => ['/admin/users/list'];
        yield 'admin-users-new' => ['/admin/users/new'];

        // Formations
        yield 'admin-formations' => ['/admin/formations'];
        yield 'admin-formations-new' => ['/admin/formations/new'];

        // Certificates
        yield 'admin-certificates' => ['/admin/certificates'];

        // Community
        yield 'admin-community' => ['/admin/community'];

        // Support
        yield 'admin-support' => ['/admin/support'];
        yield 'admin-support-avis' => ['/admin/support/avis'];

        // Finance
        yield 'admin-finance' => ['/admin/finance'];
        yield 'admin-finance-bank-accounts' => ['/admin/finance/bank-accounts'];
        yield 'admin-finance-bank-accounts-new' => ['/admin/finance/bank-accounts/new'];
        yield 'admin-finance-contracts' => ['/admin/finance/contracts'];
        yield 'admin-finance-contracts-new' => ['/admin/finance/contracts/new'];
        yield 'admin-finance-bonuses' => ['/admin/finance/bonuses'];
        yield 'admin-finance-bonuses-new' => ['/admin/finance/bonuses/new'];
        yield 'admin-finance-payslips' => ['/admin/finance/payslips'];
        yield 'admin-finance-payslips-new' => ['/admin/finance/payslips/new'];
        yield 'admin-finance-forecast' => ['/admin/finance/forecast'];
        yield 'admin-finance-reports' => ['/admin/finance/reports'];
        yield 'admin-finance-project-payment' => ['/admin/finance/project-payment'];

        // Finance API endpoints (JSON)
        yield 'admin-finance-api-overview' => ['/admin/finance/reports/api/overview'];

        // Recruitment
        yield 'admin-recruitment' => ['/admin/recruitment'];
    }

    /**
     * Verify a single admin page denies access to non-admin users.
     */
    public function testAdminPageDeniedForRegularUser(): void
    {
        // Need a fresh kernel — ensureKernelShutdown first
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['role' => 'USER', 'active' => true]);
        if (!$user) {
            $this->markTestSkipped('No USER in database — cannot run access denial test.');
        }

        $client->loginUser($user);
        $client->request('GET', '/admin/users');
        $status = $client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, '500 on /admin/users as regular user');
        $this->assertContains($status, [403, 302, 301], "Expected 403 or redirect for non-admin, got $status");
    }
}

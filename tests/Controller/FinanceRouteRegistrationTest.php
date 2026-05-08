<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class FinanceRouteRegistrationTest extends KernelTestCase
{
    /**
     * @dataProvider routeNames
     */
    public function testFinanceRouteExists(string $routeName): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        self::assertNotNull($router->getRouteCollection()->get($routeName), sprintf('Route %s should exist.', $routeName));
    }

    public static function routeNames(): iterable
    {
        yield 'finance-dashboard' => ['app_finance'];
        yield 'finance-invoices' => ['app_finance_invoices'];
        yield 'finance-invoice-show' => ['app_finance_invoice_show'];
        yield 'finance-escrow' => ['app_finance_escrow'];
        yield 'old-freelancer-invoices' => ['app_freelancer_invoices'];
        yield 'old-employer-invoices' => ['app_employer_invoices'];
        yield 'old-invoice-show' => ['app_invoice_show'];
        yield 'old-escrow' => ['app_escrow_index'];
        yield 'contracts' => ['app_contracts'];
        yield 'contract-show' => ['app_contract_show'];
        yield 'fund' => ['app_contract_fund'];
        yield 'delivery' => ['app_contract_delivery_submit'];
        yield 'approve' => ['app_contract_approve'];
        yield 'release' => ['app_contract_release'];
        yield 'dispute' => ['app_contract_dispute'];
        yield 'admin-finance' => ['app_admin_finance'];
        yield 'admin-contract' => ['app_admin_finance_contract_show'];
        yield 'admin-dispute-resolve' => ['app_admin_finance_dispute_resolve'];
    }
}

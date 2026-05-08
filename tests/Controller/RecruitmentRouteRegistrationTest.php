<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class RecruitmentRouteRegistrationTest extends KernelTestCase
{
    /**
     * @dataProvider routeNames
     */
    public function testRecruitmentRouteExists(string $routeName): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        self::assertNotNull($router->getRouteCollection()->get($routeName), sprintf('Route %s should exist.', $routeName));
    }

    public static function routeNames(): iterable
    {
        yield 'apply' => ['app_job_apply'];
        yield 'old-apply' => ['app_candidate_job_apply'];
        yield 'application-cv' => ['app_application_cv'];
        yield 'preferences' => ['app_candidate_job_preferences'];
        yield 'old-preferences' => ['app_candidate_preferences_old'];
    }
}

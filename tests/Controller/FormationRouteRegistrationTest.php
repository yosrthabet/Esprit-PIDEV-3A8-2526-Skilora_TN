<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class FormationRouteRegistrationTest extends KernelTestCase
{
    /**
     * @dataProvider routeNames
     */
    public function testFormationRouteExists(string $routeName): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);

        self::assertNotNull($router->getRouteCollection()->get($routeName), sprintf('Route %s should exist.', $routeName));
    }

    public static function routeNames(): iterable
    {
        yield 'catalog' => ['app_formations'];
        yield 'old-catalog-alias' => ['app_formation_index'];
        yield 'formation-show' => ['app_formation_show'];
        yield 'enroll' => ['app_formation_enroll'];
        yield 'learning' => ['app_learning'];
        yield 'learning-show' => ['app_learning_show'];
        yield 'progress' => ['app_learning_progress'];
        yield 'certificates' => ['app_certificates'];
        yield 'certificate-show' => ['app_certificate_show'];
        yield 'certificate-verify' => ['app_certificate_verify'];
        yield 'old-certificate-verify' => ['certificate_verify'];
        yield 'certificate-preview' => ['app_certificate_preview'];
        yield 'certificate-qr' => ['app_certificate_qr'];
        yield 'old-certificate-qr' => ['certificate_qr'];
        yield 'certificate-download' => ['app_certificate_download'];
        yield 'old-certificate-pdf' => ['certificate_pdf'];
        yield 'formation-review' => ['app_formation_review'];
        yield 'trainer-index' => ['app_trainer_formations'];
        yield 'trainer-new' => ['app_trainer_formation_new'];
        yield 'trainer-edit' => ['app_trainer_formation_edit'];
        yield 'trainer-delete' => ['app_trainer_formation_delete'];
        yield 'trainer-modules' => ['app_trainer_formation_modules'];
        yield 'trainer-material-add' => ['app_trainer_formation_material_add'];
        yield 'trainer-materials' => ['app_trainer_formation_materials'];
        yield 'trainer-module-delete' => ['app_trainer_formation_module_delete'];
        yield 'trainer-material-delete' => ['app_trainer_formation_material_delete'];
        yield 'trainer-students' => ['app_trainer_formation_students'];
        yield 'admin-index' => ['app_admin_formations'];
        yield 'admin-new' => ['app_admin_formation_new'];
        yield 'admin-show' => ['app_admin_formation_show'];
        yield 'admin-edit' => ['app_admin_formation_edit'];
        yield 'admin-publish' => ['app_admin_formation_publish'];
        yield 'admin-archive' => ['app_admin_formation_archive'];
        yield 'admin-delete' => ['app_admin_formation_delete'];
    }
}

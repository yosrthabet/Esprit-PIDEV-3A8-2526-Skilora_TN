<?php

namespace App\Tests\Integration;

use App\Service\GeminiService;
use App\Service\PublicTranslationService;
use App\Service\SupportNotificationService;
use App\Twig\AppExtension;
use App\Validator\NoBadWords;
use App\Validator\NoBadWordsValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Smoke test: verifies all synced support features are properly wired
 * in the Symfony container — services, validators, templates, routes.
 */
class SupportSmokeTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    // ── Services registered ──

    public function testGeminiServiceRegistered(): void
    {
        $service = static::getContainer()->get(GeminiService::class);
        $this->assertInstanceOf(GeminiService::class, $service);
    }

    public function testPublicTranslationServiceRegistered(): void
    {
        $service = static::getContainer()->get(PublicTranslationService::class);
        $this->assertInstanceOf(PublicTranslationService::class, $service);
    }

    public function testSupportNotificationServiceRegistered(): void
    {
        $service = static::getContainer()->get(SupportNotificationService::class);
        $this->assertInstanceOf(SupportNotificationService::class, $service);
    }

    // ── Twig extension ──

    public function testAppExtensionRegistered(): void
    {
        $twig = static::getContainer()->get('twig');
        $filter = $twig->getFilter('json_decode');
        $this->assertNotNull($filter, 'Twig filter json_decode should be registered');
    }

    // ── Validator ──

    public function testNoBadWordsConstraintExists(): void
    {
        $constraint = new NoBadWords();
        $this->assertNotEmpty($constraint->badWords);
        $this->assertStringContainsString('{{ words }}', $constraint->message);
    }

    public function testNoBadWordsValidatorExists(): void
    {
        $validator = new NoBadWordsValidator();
        $this->assertInstanceOf(NoBadWordsValidator::class, $validator);
    }

    // ── Routes exist ──

    public function testAllNewRoutesExist(): void
    {
        $router = static::getContainer()->get('router');
        $routes = $router->getRouteCollection();

        $expectedRoutes = [
            // Admin controller new routes
            'admin_support_calendar',
            'admin_support_download_pdf',
            'admin_support_message_translate',
            'admin_support_message_analyze_mood',
            'admin_support_translate_text',
            'admin_support_ticket_translate',
            // Client controller new routes
            'support_ai_suggest_subject',
            'support_ai_correct_text',
            'support_download_pdf',
            // Existing routes (sanity check)
            'admin_support_index',
            'admin_support_show',
            'admin_support_message_create',
            'support_index',
            'support_new',
            'support_show',
            'support_message_create',
            'support_close',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertNotNull(
                $routes->get($routeName),
                sprintf('Route "%s" should exist', $routeName)
            );
        }
    }

    // ── Templates exist ──

    public function testNewTemplatesExist(): void
    {
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');

        $templates = [
            'templates/support/admin/calendar.html.twig',
            'templates/support/admin/pdf_export.html.twig',
            'templates/support/client/_ticket_grid.html.twig',
            'templates/support/client/pdf_export.html.twig',
            'templates/support/emails/status_change.html.twig',
        ];

        foreach ($templates as $template) {
            $this->assertFileExists(
                $projectDir . '/' . $template,
                sprintf('Template "%s" should exist', $template)
            );
        }
    }

    // ── Entity schema ──

    public function testMessageTicketHasSentimentField(): void
    {
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $metadata = $em->getClassMetadata(\App\Entity\MessageTicket::class);

        $this->assertTrue(
            $metadata->hasField('sentiment'),
            'MessageTicket entity should have sentiment field'
        );
    }

    // ── Form type ──

    public function testMessageTicketFormHasAttachmentFiles(): void
    {
        $formFactory = static::getContainer()->get('form.factory');
        $form = $formFactory->create(\App\Form\MessageTicketType::class, null, ['is_admin' => true]);

        $this->assertTrue(
            $form->has('attachmentFiles'),
            'MessageTicketType should have attachmentFiles field'
        );
    }
}

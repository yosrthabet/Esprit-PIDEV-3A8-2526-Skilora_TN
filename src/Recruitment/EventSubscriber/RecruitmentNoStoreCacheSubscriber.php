<?php

declare(strict_types=1);

namespace App\Recruitment\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Évite la mise en cache navigateur / proxy des pages recrutement (données fraîches après POST).
 */
final class RecruitmentNoStoreCacheSubscriber implements EventSubscriberInterface
{
    private const PREFIXES = [
        '/offres',
        '/mon-espace',
        '/employer/candidatures',
    ];

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onKernelResponse', -10]];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        foreach (self::PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                $event->getResponse()->headers->set('Cache-Control', 'private, no-store, must-revalidate');
                $event->getResponse()->headers->set('Pragma', 'no-cache');

                return;
            }
        }
    }
}

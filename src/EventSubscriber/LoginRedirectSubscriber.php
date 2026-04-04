<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LoginRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!method_exists($user, 'getRoles')) {
            return;
        }

        $roles = $user->getRoles();

        if (\in_array('ROLE_EMPLOYER', $roles, true)) {
            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate('app_employer_dashboard'),
            ));

            return;
        }

        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('app_candidate_offres_index'),
        ));
    }
}

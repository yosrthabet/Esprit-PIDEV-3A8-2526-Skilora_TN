<?php

namespace App\EventSubscriber;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Entity\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $ip = $request->getClientIp();
        $ua = $request->headers->get('User-Agent', '');

        // Record login history
        $history = new LoginHistory();
        $history->setUser($user);
        $history->setIp($ip);
        $history->setUserAgent($ua);
        $history->setStatus('success');
        $history->setMethod($this->resolveMethod($event));
        $this->em->persist($history);

        // Track session
        $sessionId = $request->getSession()->getId();
        if ($sessionId) {
            $existing = $this->em->getRepository(UserSession::class)
                ->findOneBy(['sessionId' => $sessionId]);

            if (!$existing) {
                $session = new UserSession();
                $session->setUser($user);
                $session->setSessionId($sessionId);
                $session->setIp($ip);
                $session->setUserAgent($ua);
                $this->em->persist($session);
            }
        }

        $this->em->flush();
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $username = $request->getPayload()->getString('_username');

        if (!$username) {
            return;
        }

        // Try to find the user by username to record the failed attempt
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        if (!$user) {
            return;
        }

        $history = new LoginHistory();
        $history->setUser($user);
        $history->setIp($request->getClientIp());
        $history->setUserAgent($request->headers->get('User-Agent', ''));
        $history->setStatus('failure');
        $history->setMethod('password');
        $this->em->persist($history);
        $this->em->flush();
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $sessionId = $request->getSession()->getId();

        if ($sessionId) {
            $session = $this->em->getRepository(UserSession::class)
                ->findOneBy(['sessionId' => $sessionId]);

            if ($session) {
                $this->em->remove($session);
                $this->em->flush();
            }
        }
    }

    private function resolveMethod(LoginSuccessEvent $event): string
    {
        $firewallName = $event->getFirewallName();
        $authenticator = $event->getAuthenticator();
        $class = get_class($authenticator);

        if (str_contains($class, 'Google')) return 'google';
        if (str_contains($class, 'GitHub') || str_contains($class, 'Github')) return 'github';

        return 'password';
    }
}

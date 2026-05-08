<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ProdDebugSubscriber implements EventSubscriberInterface
{
    private float $startTime;
    private string $logFile;

    public function __construct(
        #[Autowire('%kernel.logs_dir%')] string $logsDir,
    ) {
        $this->logFile = $logsDir . '/skilora-debug.jsonl';
    }

    /** @param array<string, mixed> $data */
    private function log(string $level, array $data): void
    {
        $data['_ts']    = date('c');
        $data['_level'] = $level;
        @file_put_contents($this->logFile, json_encode($data, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST   => ['onRequest', 1024],
            KernelEvents::RESPONSE  => ['onResponse', -1024],
            KernelEvents::EXCEPTION => ['onException', 1024],
            KernelEvents::TERMINATE => ['onTerminate', -1024],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $this->startTime = microtime(true);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request  = $event->getRequest();
        $response = $event->getResponse();
        $elapsed  = round((microtime(true) - ($this->startTime ?? microtime(true))) * 1000);
        $user     = $request->getUser() ?? 'anon';

        $token = $request->getSession()->get('_security_main');
        $username = 'anon';
        if (is_string($token) && $token !== '') {
            try {
                $unserialized = unserialize($token);
                if (is_object($unserialized) && method_exists($unserialized, 'getUserIdentifier')) {
                    $username = $unserialized->getUserIdentifier();
                }
            } catch (\Throwable) {
            }
        }

        $this->log('info', [
            'method'   => $request->getMethod(),
            'uri'      => $request->getRequestUri(),
            'status'   => $response->getStatusCode(),
            'ms'       => $elapsed,
            'user'     => $username,
            'ip'       => $request->getClientIp(),
            'size'     => strlen($response->getContent() ?: ''),
        ]);
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $e       = $event->getThrowable();

        $this->log('error', [
            'uri'     => $request->getRequestUri(),
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        ]);
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $response = $event->getResponse();
        if ($response->getStatusCode() >= 500) {
            $this->log('critical', [
                'uri'    => $event->getRequest()->getRequestUri(),
                'status' => $response->getStatusCode(),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Support\Repository\SupportTicketRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private const PRIORITY_COLORS = [
        'CRITICAL' => '#ef4444',
        'HIGH'     => '#f97316',
        'MEDIUM'   => '#eab308',
        'LOW'      => '#22c55e',
    ];

    private const STATUS_COLORS = [
        'OPEN'        => '#3b82f6',
        'IN_PROGRESS' => '#8b5cf6',
        'RESOLVED'    => '#22c55e',
        'CLOSED'      => '#6b7280',
    ];

    public function __construct(
        private readonly SupportTicketRepository $ticketRepository,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();

        $tickets = $this->ticketRepository->createQueryBuilder('t')
            ->where('t.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        foreach ($tickets as $ticket) {
            $priority = $ticket->getPriority()->value ?? '';
            $status = $ticket->getStatus()->value ?? '';
            $color = self::PRIORITY_COLORS[strtoupper($priority)]
                ?? self::STATUS_COLORS[strtoupper($status)]
                ?? '#3b82f6';

            $event = new Event(
                sprintf('#%d — %s', $ticket->getId(), $ticket->getSubject()),
                $ticket->getCreatedAt(),
                $ticket->getResolvedAt(),
                null,
                [
                    'url' => $this->router->generate('app_admin_support_show', ['id' => $ticket->getId()]),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#ffffff',
                ]
            );

            $calendar->addEvent($event);
        }
    }
}

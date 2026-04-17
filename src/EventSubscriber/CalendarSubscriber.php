<?php

namespace App\EventSubscriber;

use App\Repository\TicketRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private UrlGeneratorInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $calendarEvent): void
    {
        $start = $calendarEvent->getStart();
        $end = $calendarEvent->getEnd();
        $filters = $calendarEvent->getFilters();

        $tickets = $this->ticketRepository->findAll();

        foreach ($tickets as $ticket) {
            $startDate = $ticket->getDateCreation();
            if ($startDate instanceof \DateTimeImmutable) {
                $startDate = \DateTime::createFromImmutable($startDate);
            }

            $ticketEvent = new Event(
                $ticket->getSubject(),
                $startDate,
                $startDate // Using same date for both since it's a point in time
            );

            /*
             * Optional: Add custom options to the event
             */
            $ticketEvent->setOptions([
                'backgroundColor' => $this->getColorByStatus($ticket->getStatut()),
                'borderColor' => $this->getColorByStatus($ticket->getStatut()),
            ]);
            $ticketEvent->addOption(
                'url',
                $this->router->generate('admin_support_show', [
                    'id' => $ticket->getId(),
                ])
            );

            $calendarEvent->addEvent($ticketEvent);
        }
    }

    private function getColorByStatus(string $status): string
    {
        return match ($status) {
            'OPEN' => '#ef4444', // Red
            'IN_PROGRESS' => '#3b82f6', // Blue
            'RESOLVED' => '#10b981', // Emerald
            'CLOSED' => '#71717a', // Zinc
            default => '#2563eb',
        };
    }
}

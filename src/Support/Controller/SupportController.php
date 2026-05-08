<?php

declare(strict_types=1);

namespace App\Support\Controller;

use App\Controller\AppController;
use App\Enum\TicketCategory;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Support\Entity\SupportMessage;
use App\Support\Entity\SupportTicket;
use App\Support\Repository\SupportMessageRepository;
use App\Support\Repository\SupportTicketRepository;
use App\Support\Service\SupportNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SupportController extends AppController
{
    public function __construct(
        private readonly SupportTicketRepository $ticketRepository,
        private readonly SupportMessageRepository $messageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SupportNotifier $notifier,
    ) {
    }

    #[Route('/support', name: 'app_support', methods: ['GET'])]
    #[Route('/support-space', name: 'app_support_legacy', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getAppUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_support');
        }

        return $this->render('support/client/index.html.twig', [
            'tickets' => $this->ticketRepository->findForUser($user, $request->query->getString('q') ?: null),
        ]);
    }

    #[Route('/support/new', name: 'app_support_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_support');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('support_ticket_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $ticket = (new SupportTicket())
                ->setRequester($this->getAppUser())
                ->setSubject(trim($request->request->getString('subject')))
                ->setDescription(trim($request->request->getString('description')))
                ->setCategory(TicketCategory::tryFrom($request->request->getString('category')) ?? TicketCategory::OTHER)
                ->setPriority(TicketPriority::tryFrom($request->request->getString('priority')) ?? TicketPriority::NORMAL);
            $this->entityManager->persist($ticket);
            $this->entityManager->flush();
            $this->notifier->notifyAdminsNewTicket($ticket);

            return $this->redirectToRoute('app_support_show', ['id' => $ticket->getId()]);
        }

        return $this->render('support/client/new.html.twig');
    }

    #[Route('/support/{id}', name: 'app_support_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Route('/admin/support/{id}', name: 'app_admin_support_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(SupportTicket $ticket): Response
    {
        $admin = $this->isGranted('ROLE_ADMIN');
        $this->assertTicketAccess($ticket, $admin);

        return $this->render('support/show.html.twig', [
            'ticket' => $ticket,
            'messages' => $this->messageRepository->findVisibleForTicket($ticket, $admin),
            'admin' => $admin,
        ]);
    }

    #[Route('/support/{id}/messages', name: 'app_support_messages', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[Route('/admin/support/{id}/messages', name: 'app_admin_support_messages', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function messages(SupportTicket $ticket, Request $request): JsonResponse
    {
        $admin = $this->isGranted('ROLE_ADMIN');
        $this->assertTicketAccess($ticket, $admin);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('support_message_' . $ticket->getId(), $request->request->getString('_token'))) {
                return new JsonResponse(['error' => 'Invalid token'], 403);
            }
            $body = trim($request->request->getString('body'));
            if ($body === '') {
                return new JsonResponse(['error' => 'Message is required'], 422);
            }
            $message = (new SupportMessage())
                ->setTicket($ticket)
                ->setSender($this->getAppUser())
                ->setBody($body)
                ->setInternalNote($admin && $request->request->getBoolean('internal'));
            $ticket->touch();
            if ($admin && $ticket->getStatus() === TicketStatus::OPEN) {
                $ticket->setStatus(TicketStatus::IN_PROGRESS);
            }
            $this->entityManager->persist($message);
            $this->entityManager->flush();
            if ($admin && !$message->isInternalNote()) {
                $this->notifier->notifyRequester($ticket, 'Support replied', 'A support agent replied to your ticket.');
            }

            return new JsonResponse(['message' => $this->serializeMessage($message)]);
        }

        $afterId = $request->query->getInt('after', 0);
        return new JsonResponse([
            'messages' => array_map(fn (SupportMessage $message): array => $this->serializeMessage($message), $this->messageRepository->findVisibleForTicket($ticket, $admin, $afterId)),
            'status' => $ticket->getStatus()->label(),
        ]);
    }

    #[Route('/admin/support', name: 'app_admin_support', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(Request $request): Response
    {
        return $this->render('support/admin/index.html.twig', [
            'tickets' => $this->ticketRepository->findForAdmin($request->query->getString('status') ?: null, $request->query->getString('q') ?: null),
            'current_status' => $request->query->getString('status') ?: 'all',
        ]);
    }

    #[Route('/admin/support/{id}/status', name: 'app_admin_support_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function status(SupportTicket $ticket, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('support_status_' . $ticket->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $old = $ticket->getStatus();
        $ticket->setStatus(TicketStatus::tryFrom($request->request->getString('status')) ?? $ticket->getStatus());
        $ticket->setPriority(TicketPriority::tryFrom($request->request->getString('priority')) ?? $ticket->getPriority());
        $ticket->setAssignedTo($this->getAppUser());
        $this->entityManager->flush();
        if ($old !== $ticket->getStatus()) {
            $this->notifier->notifyRequester($ticket, 'Support status updated', 'Your ticket is now ' . $ticket->getStatus()->label() . '.');
        }

        return $this->redirectToRoute('app_admin_support_show', ['id' => $ticket->getId()]);
    }

    private function assertTicketAccess(SupportTicket $ticket, bool $admin): void
    {
        if (!$admin && $ticket->getRequester()->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    /** @return array<string, mixed> */
    private function serializeMessage(SupportMessage $message): array
    {
        return [
            'id' => $message->getId(),
            'body' => $message->getBody(),
            'sender' => $message->getSender()->getDisplayName(),
            'senderId' => $message->getSender()->getId(),
            'internal' => $message->isInternalNote(),
            'createdAt' => $message->getCreatedAt()->format('M d, H:i'),
            'mine' => $message->getSender()->getId() === $this->getAppUser()->getId(),
        ];
    }
}

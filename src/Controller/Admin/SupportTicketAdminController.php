<?php

namespace App\Controller\Admin;

use App\Entity\MessageTicket;
use App\Entity\Ticket;
use App\Form\MessageTicketType;
use App\Form\TicketAdminType;
use App\Repository\FeedbackRepository;
use App\Repository\MessageTicketRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/support', name: 'admin_support_')]
class SupportTicketAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository, FeedbackRepository $feedbackRepository): Response
    {
        return $this->render('support/admin/index.html.twig', [
            'tickets' => $ticketRepository->findBy([], ['dateCreation' => 'DESC']),
            'status_stats' => $ticketRepository->countByStatus(),
            'avg_rating' => $feedbackRepository->getAverageRating(),
        ]);
    }

    #[Route('/avis', name: 'avis_index', methods: ['GET'])]
    public function avis(FeedbackRepository $feedbackRepository): Response
    {
        return $this->render('support/admin/avis.html.twig', [
            'feedbacks' => $feedbackRepository->findBy([], ['dateCreation' => 'DESC']),
            'avg_rating' => $feedbackRepository->getAverageRating(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository
    ): Response {
        $adminForm = $this->createForm(TicketAdminType::class, $ticket);
        $adminForm->handleRequest($request);

        if ($adminForm->isSubmitted() && $adminForm->isValid()) {
            if (\in_array($ticket->getStatut(), ['RESOLVED', 'CLOSED'], true) && !$ticket->getDateResolution()) {
                $ticket->setDateResolution(new \DateTime());
            }
            if (!\in_array($ticket->getStatut(), ['RESOLVED', 'CLOSED'], true)) {
                $ticket->setDateResolution(null);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Ticket updated.');

            return $this->redirectToRoute('admin_support_show', ['id' => $ticket->getId()]);
        }

        return $this->renderShowPage($ticket, $request, $entityManager, $messageRepository);
    }

    #[Route('/{id}/messages', name: 'message_create', methods: ['POST'])]
    public function createMessage(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository
    ): Response {
        $message = (new MessageTicket())
            ->setTicket($ticket)
            ->setUtilisateurId($this->resolveAdminId())
            ->setIsInternal(false);

        $form = $this->createForm(MessageTicketType::class, $message, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();
            $this->addFlash('success', 'Message posted.');
        } else {
            $this->addFlash('error', 'Message not sent. Please check fields.');
        }

        return $this->renderShowPage($ticket, $request, $entityManager, $messageRepository);
    }

    #[Route('/{ticketId}/messages/{messageId}/edit', name: 'message_edit', methods: ['GET', 'POST'])]
    public function editMessage(
        int $ticketId,
        int $messageId,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository
    ): Response {
        $message = $messageRepository->find($messageId);
        if (!$message || !$message->getTicket() || $message->getTicket()->getId() !== $ticketId) {
            throw $this->createNotFoundException('Message not found.');
        }

        $form = $this->createForm(MessageTicketType::class, $message, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Message updated.');

            return $this->redirectToRoute('admin_support_show', ['id' => $ticketId]);
        }

        return $this->render('support/admin/edit_message.html.twig', [
            'ticket' => $message->getTicket(),
            'message' => $message,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{ticketId}/messages/{messageId}/delete', name: 'message_delete', methods: ['POST'])]
    public function deleteMessage(
        int $ticketId,
        int $messageId,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository
    ): Response {
        $message = $messageRepository->find($messageId);
        if (!$message || !$message->getTicket() || $message->getTicket()->getId() !== $ticketId) {
            throw $this->createNotFoundException('Message not found.');
        }

        if (!$this->isCsrfTokenValid('delete_message_'.$messageId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid delete token.');

            return $this->redirectToRoute('admin_support_show', ['id' => $ticketId]);
        }

        $entityManager->remove($message);
        $entityManager->flush();
        $this->addFlash('success', 'Message deleted.');

        return $this->redirectToRoute('admin_support_show', ['id' => $ticketId]);
    }

    private function resolveAdminId(): int
    {
        // Hardcoded demo mapping without auth/security.
        return 1;
    }

    private function renderShowPage(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository
    ): Response {
        $adminForm = $this->createForm(TicketAdminType::class, $ticket);
        $adminForm->handleRequest($request);

        if ($adminForm->isSubmitted() && $adminForm->isValid()) {
            if (\in_array($ticket->getStatut(), ['RESOLVED', 'CLOSED'], true) && !$ticket->getDateResolution()) {
                $ticket->setDateResolution(new \DateTime());
            }
            if (!\in_array($ticket->getStatut(), ['RESOLVED', 'CLOSED'], true)) {
                $ticket->setDateResolution(null);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Ticket updated.');
        }

        $newMessage = (new MessageTicket())
            ->setTicket($ticket)
            ->setUtilisateurId($this->resolveAdminId())
            ->setIsInternal(false);

        $messageForm = $this->createForm(MessageTicketType::class, $newMessage, [
            'is_admin' => true,
            'action' => $this->generateUrl('admin_support_message_create', ['id' => $ticket->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('support/admin/show.html.twig', [
            'ticket' => $ticket,
            'messages' => $messageRepository->findByTicket($ticket, true),
            'admin_form' => $adminForm->createView(),
            'message_form' => $messageForm->createView(),
        ]);
    }
}

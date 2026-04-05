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
    public function index(Request $request, TicketRepository $ticketRepository, FeedbackRepository $feedbackRepository): Response
    {
        $query = $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 3;

        $tickets = $ticketRepository->search($query, $page, $limit);
        $totalItems = $ticketRepository->countTotal($query);
        $totalPages = ceil($totalItems / $limit);

        if ($request->isXmlHttpRequest()) {
            return $this->render('support/admin/_ticket_table.html.twig', [
                'tickets' => $tickets,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'search_query' => $query,
            ]);
        }

        return $this->render('support/admin/index.html.twig', [
            'tickets' => $tickets,
            'status_stats' => $ticketRepository->countByStatus(),
            'priority_stats' => $ticketRepository->countByPriority(),
            'avg_rating' => $feedbackRepository->getAverageRating(),
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search_query' => $query,
        ]);
    }

    #[Route('/avis', name: 'avis_index', methods: ['GET'])]
    public function avis(Request $request, FeedbackRepository $feedbackRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 5;

        $feedbacks = $feedbackRepository->search($page, $limit);
        $totalItems = $feedbackRepository->countAll();
        $totalPages = ceil($totalItems / $limit);

        if ($request->isXmlHttpRequest()) {
            return $this->render('support/admin/_avis_grid.html.twig', [
                'feedbacks' => $feedbacks,
                'current_page' => $page,
                'total_pages' => $totalPages,
            ]);
        }

        return $this->render('support/admin/avis.html.twig', [
            'feedbacks' => $feedbacks,
            'avg_rating' => $feedbackRepository->getAverageRating(),
            'rating_dist' => $feedbackRepository->getRatingDistribution(),
            'current_page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/export/csv', name: 'export_csv', methods: ['GET'])]
    public function exportCsv(TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findAll();
        $csv = "ID,Subject,Category,Priority,Status,Created At\n";

        foreach ($tickets as $ticket) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $ticket->getId(),
                str_replace('"', '""', $ticket->getSubject() ?? ''),
                str_replace('"', '""', $ticket->getCategorie() ?? ''),
                $ticket->getPriorite(),
                $ticket->getStatut(),
                $ticket->getDateCreation()?->format('Y-m-d H:i') ?? '-'
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="tickets_' . date('Y-m-d') . '.csv"');

        return $response;
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
        } elseif ($request->isMethod('POST')) {
            $post = $request->request->all();
            $contenu = null;
            $attachmentsJson = null;
            $isInternal = null;

            foreach ($post as $val) {
                if (\is_array($val)) {
                    $contenu = $val['contenu'] ?? $contenu;
                    $attachmentsJson = $val['attachmentsJson'] ?? $attachmentsJson;
                    $isInternal = $val['isInternal'] ?? $isInternal;
                }
            }
            $contenu ??= ($post['contenu'] ?? null);
            $attachmentsJson ??= ($post['attachmentsJson'] ?? null);
            $isInternal ??= ($post['isInternal'] ?? null);

            if ($contenu !== null && trim($contenu) !== '') {
                $message->setContenu(trim($contenu));
                if ($attachmentsJson !== null) {
                    $message->setAttachmentsJson(trim($attachmentsJson));
                }
                if ($isInternal !== null) {
                    $message->setIsInternal((bool) $isInternal);
                }
                $entityManager->flush();
                $this->addFlash('success', 'Message updated.');
                return $this->redirectToRoute('admin_support_show', ['id' => $ticketId]);
            }
            
            $this->addFlash('error', 'Update failed: Message cannot be empty.');
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

        if (!$this->isCsrfTokenValid('delete_message_' . $messageId, (string) $request->request->get('_token'))) {
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

        $query = $request->query->get('mq', '');
        $messages = $messageRepository->searchByTicket($ticket, $query);

        if ($request->isXmlHttpRequest() && $request->query->has('mq')) {
            return $this->render('support/admin/_message_list.html.twig', [
                'messages' => $messages,
                'ticket' => $ticket,
            ]);
        }

        return $this->render('support/admin/show.html.twig', [
            'ticket' => $ticket,
            'messages' => $messages,
            'admin_form' => $adminForm->createView(),
            'message_form' => $messageForm->createView(),
            'search_query' => $query,
        ]);
    }
}

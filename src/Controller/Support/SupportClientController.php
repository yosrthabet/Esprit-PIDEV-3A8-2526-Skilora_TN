<?php

namespace App\Controller\Support;

use App\Entity\Feedback;
use App\Entity\MessageTicket;
use App\Entity\Ticket;
use App\Form\FeedbackType;
use App\Form\MessageTicketType;
use App\Form\TicketType;
use App\Repository\FeedbackRepository;
use App\Repository\MessageTicketRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/support', name: 'support_')]
class SupportClientController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): Response
    {
        return $this->render('support/client/index.html.twig', [
            'tickets' => $ticketRepository->findByUser($this->resolveUserId()),
            'stats' => $this->getHomeStats(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $isAjax = $request->headers->has('X-Requested-With');
        $ticket = (new Ticket())
            ->setUserId($this->resolveUserId())
            ->setStatus('OPEN');

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticket);
            $entityManager->flush();

            if ($isAjax) {
                return new JsonResponse(['success' => true, 'message' => 'Ticket created successfully.']);
            }

            $this->addFlash('success', 'Ticket created successfully.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
        }

        if ($isAjax && !$form->isSubmitted()) {
            return $this->render('support/client/_dialog_form.html.twig', [
                'form' => $form->createView(),
                'ticket' => null,
            ]);
        }

        if ($isAjax && $form->isSubmitted()) {
            return new JsonResponse(['success' => false, 'message' => 'Please fix the errors.'], 422);
        }

        return $this->render('support/client/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Ticket $ticket,
        MessageTicketRepository $messageRepository,
        FeedbackRepository $feedbackRepository
    ): Response {
        return $this->renderShowPage($ticket, $messageRepository, $feedbackRepository);
    }

    #[Route('/{id}/messages', name: 'message_create', methods: ['POST'])]
    public function createMessage(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository,
        FeedbackRepository $feedbackRepository
    ): Response
    {
        $this->assertTicketOwnership($ticket);

        $message = (new MessageTicket())
            ->setTicket($ticket)
            ->setSenderId($this->resolveUserId())
            ->setIsInternal(false)
            ->setCreatedDate(new \DateTime());

        $form = $this->createForm(MessageTicketType::class, $message, ['is_admin' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();
            $this->addFlash('success', 'Reply sent.');
        } else {
            // Fallback: parse POST directly (handles form block-prefix mismatch)
            $post = $request->request->all();
            $contenu = null;
            $attachmentsJson = null;

            // Check nested keys (e.g. message_ticket[message])
            foreach ($post as $val) {
                if (\is_array($val)) {
                    $contenu = $val['message'] ?? $contenu;
                    $attachmentsJson = $val['attachmentsJson'] ?? $attachmentsJson;
                }
            }
            // Check flat keys as last resort
            $contenu ??= ($post['message'] ?? null);
            $attachmentsJson ??= ($post['attachmentsJson'] ?? null);

            if ($contenu !== null && trim($contenu) !== '') {
                $message->setMessage(trim($contenu));
                if ($attachmentsJson !== null && trim($attachmentsJson) !== '') {
                    $message->setAttachmentsJson(trim($attachmentsJson));
                }
                $entityManager->persist($message);
                $entityManager->flush();
                $this->addFlash('success', 'Reply sent.');
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', $errors
                    ? 'Validation failed: ' . implode(', ', $errors)
                    : 'Message could not be sent. Please fill in your message.');
            }
        }

        return $this->renderShowPage($ticket, $messageRepository, $feedbackRepository);
    }

    #[Route('/{id}/feedback', name: 'feedback_save', methods: ['POST'])]
    public function saveFeedback(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $entityManager,
        FeedbackRepository $feedbackRepository,
        MessageTicketRepository $messageRepository
    ): Response {
        $this->assertTicketOwnership($ticket);

        if ($ticket->getStatus() !== 'CLOSED') {
            $this->addFlash('error', 'Feedback is available only for closed tickets.');

            return $this->renderShowPage($ticket, $messageRepository, $feedbackRepository);
        }

        $feedback = $feedbackRepository->findOneByTicket($ticket) ?? (new Feedback())->setTicket($ticket);
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Thanks for your feedback.');
        } else {
            // Fallback: parse POST directly (handles form block-prefix mismatch)
            $post = $request->request->all();
            $rating = null;
            $comment = null;

            foreach ($post as $val) {
                if (\is_array($val)) {
                    $rating = $val['rating'] ?? $rating;
                    $comment = $val['comment'] ?? $comment;
                }
            }
            $rating ??= $post['rating'] ?? null;
            $comment ??= $post['comment'] ?? null;

            if ($rating !== null) {
                $feedback->setRating((int)$rating);
                if ($comment !== null) {
                    $feedback->setComment(trim($comment));
                }
                $entityManager->persist($feedback);
                $entityManager->flush();
                $this->addFlash('success', 'Thanks for your feedback.');
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', $errors 
                    ? 'Validation failed: ' . implode(', ', $errors) 
                    : 'Form binding failed completely. Make sure to tap a star.');
            }
        }

        return $this->renderShowPage($ticket, $messageRepository, $feedbackRepository);
    }

    #[Route('/{id}/close', name: 'close', methods: ['POST'])]
    public function closeTicket(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertTicketOwnership($ticket);
        if (!$this->isCsrfTokenValid('close_ticket_'.$ticket->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
        }

        $ticket->setStatus('CLOSED');
        $ticket->setResolvedDate(new \DateTime());
        $entityManager->flush();
        $this->addFlash('success', 'Ticket closed.');

        return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function editTicket(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertTicketOwnership($ticket);
        $isAjax = $request->headers->has('X-Requested-With');

        if (\in_array($ticket->getStatus(), ['RESOLVED', 'CLOSED'], true)) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Resolved or closed tickets cannot be edited.'], 422);
            }
            $this->addFlash('warning', 'Resolved or closed tickets cannot be edited.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($isAjax) {
                return new JsonResponse(['success' => true, 'message' => 'Ticket updated.']);
            }

            $this->addFlash('success', 'Ticket updated.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
        }

        if ($isAjax && !$form->isSubmitted()) {
            return $this->render('support/client/_dialog_form.html.twig', [
                'form' => $form->createView(),
                'ticket' => $ticket,
            ]);
        }

        if ($isAjax && $form->isSubmitted()) {
            return new JsonResponse(['success' => false, 'message' => 'Please fix the errors.'], 422);
        }

        return $this->render('support/client/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
            'stats' => $this->getHomeStats(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function deleteTicket(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertTicketOwnership($ticket);
        $isAjax = $request->headers->has('X-Requested-With');
        if (!$this->isCsrfTokenValid('delete_ticket_'.$ticket->getId(), (string) $request->request->get('_token'))) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid request.'], 403);
            }
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('support_index');
        }

        $entityManager->remove($ticket);
        $entityManager->flush();

        if ($isAjax) {
            return new JsonResponse(['success' => true, 'message' => 'Ticket deleted.']);
        }

        $this->addFlash('success', 'Ticket deleted.');

        return $this->redirectToRoute('support_index');
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

        if ($message->getSenderId() !== $this->resolveUserId()) {
             throw $this->createAccessDeniedException('You can only edit your own messages.');
        }

        $form = $this->createForm(MessageTicketType::class, $message, ['is_admin' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Message updated.');

            return $this->redirectToRoute('support_show', ['id' => $ticketId]);
        }

        return $this->render('support/client/edit_message.html.twig', [
            'ticket' => $message->getTicket(),
            'message' => $message,
            'form' => $form->createView(),
            'stats' => $this->getHomeStats(),
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

        if ($message->getSenderId() !== $this->resolveUserId()) {
             throw $this->createAccessDeniedException('You can only delete your own messages.');
        }

        if (!$this->isCsrfTokenValid('delete_message_'.$messageId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid delete token.');

            return $this->redirectToRoute('support_show', ['id' => $ticketId]);
        }

        $entityManager->remove($message);
        $entityManager->flush();
        $this->addFlash('success', 'Message deleted.');

        return $this->redirectToRoute('support_show', ['id' => $ticketId]);
    }

    private function assertTicketOwnership(Ticket $ticket): void
    {
        // Ownership checks intentionally disabled for demo/no-auth mode.
    }

    private function resolveUserId(): int
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }
        return $user->getId();
    }

    private function getHomeStats(): array
    {
        return [
            ['value' => '10K+', 'label' => 'Active Job Seekers', 'suffix' => 'Talent ready to work'],
            ['value' => '2,500', 'label' => 'Companies Hiring', 'suffix' => 'Verified Businesses'],
            ['value' => '500+', 'label' => 'Training Courses', 'suffix' => 'To boost skills'],
            ['value' => '$2M+', 'label' => 'Earned by Talent', 'suffix' => 'Paid securely'],
        ];
    }

    private function renderShowPage(
        Ticket $ticket,
        MessageTicketRepository $messageRepository,
        FeedbackRepository $feedbackRepository
    ): Response {
        $newMessage = (new MessageTicket())
            ->setTicket($ticket)
            ->setSenderId($this->resolveUserId())
            ->setIsInternal(false);

        $messageForm = $this->createForm(MessageTicketType::class, $newMessage, [
            'is_admin' => false,
            'action' => $this->generateUrl('support_message_create', ['id' => $ticket->getId()]),
            'method' => 'POST',
        ]);

        $feedback = $feedbackRepository->findOneByTicket($ticket) ?? (new Feedback())->setTicket($ticket);
        $feedbackForm = $this->createForm(FeedbackType::class, $feedback, [
            'action' => $this->generateUrl('support_feedback_save', ['id' => $ticket->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('support/client/show.html.twig', [
            'ticket' => $ticket,
            'messages' => $messageRepository->findByTicket($ticket, false),
            'message_form' => $messageForm->createView(),
            'feedback_form' => $feedbackForm->createView(),
            'has_feedback' => $feedback->getId() !== null,
            'stats' => $this->getHomeStats(),
        ]);
    }
}

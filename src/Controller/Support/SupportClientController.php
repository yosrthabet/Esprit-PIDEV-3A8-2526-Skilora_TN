<?php

namespace App\Controller\Support;

use App\Entity\Feedback;
use App\Entity\MessageTicket;
use App\Entity\Ticket;
use App\Form\FeedbackType;
use App\Form\MessageTicketType;
use App\Form\TicketType;
use App\Service\GeminiService;
use Symfony\Component\String\Slugger\SluggerInterface;
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
    public function __construct(
        private SluggerInterface $slugger
    ) {}
    #[Route('/ai/suggest-subject', name: 'ai_suggest_subject', methods: ['POST'])]
    public function suggestSubject(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $description = $data['description'] ?? '';
        
        $suggestion = $gemini->suggestSubject($description);
        
        return new JsonResponse(['suggestion' => trim($suggestion)]);
    }

    #[Route('/ai/correct-text', name: 'ai_correct_text', methods: ['POST'])]
    public function correctText(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        
        $correction = $gemini->correctText($text);
        
        return new JsonResponse(['correction' => trim($correction)]);
    }
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TicketRepository $ticketRepository): Response
    {
        $query = $request->query->get('q', '');
        $tickets = $ticketRepository->searchByUser($this->resolveUserId(), $query);

        if ($request->isXmlHttpRequest()) {
            return $this->render('support/client/_ticket_grid.html.twig', [
                'tickets' => $tickets,
            ]);
        }

        return $this->render('support/client/index.html.twig', [
            'tickets' => $tickets,
            'stats' => $this->getHomeStats(),
            'search_query' => $query,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = (new Ticket())
            ->setUtilisateurId($this->resolveUserId())
            ->setStatut('OPEN');

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Ticket created successfully.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
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
        FeedbackRepository $feedbackRepository,
        GeminiService $gemini
    ): Response {
        $this->assertTicketOwnership($ticket);

        $message = (new MessageTicket())
            ->setTicket($ticket)
            ->setUtilisateurId($this->resolveUserId())
            ->setIsInternal(false)
            ->setDateEnvoi(new \DateTime());

        $form = $this->createForm(MessageTicketType::class, $message, ['is_admin' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('attachmentFiles')->getData();
            if ($files) {
                $filenames = $this->handleFileUploads($files);
                $message->setAttachmentsJson(json_encode($filenames));
            }

            // Analyze Emotional Sentiment
            $sentiment = $gemini->detectTone($message->getContenu());
            $message->setSentiment($sentiment);

            $entityManager->persist($message);
            $entityManager->flush();
            $this->addFlash('success', 'Reply sent with emotional awareness.');
        } else {
            $post = $request->request->all();
            $contenu = null;
            $attachmentsJson = null;

            foreach ($post as $val) {
                if (\is_array($val)) {
                    $contenu = $val['contenu'] ?? $contenu;
                    $attachmentsJson = $val['attachmentsJson'] ?? $attachmentsJson;
                }
            }
            $contenu ??= ($post['contenu'] ?? null);
            $attachmentsJson ??= ($post['attachmentsJson'] ?? null);

            if ($contenu !== null && trim($contenu) !== '') {
                $message->setContenu(trim($contenu));
                if ($attachmentsJson !== null && trim($attachmentsJson) !== '') {
                    $message->setAttachmentsJson(trim($attachmentsJson));
                }
                
                // Analyze Emotional Sentiment for fallback path too
                $sentiment = $gemini->detectTone($message->getContenu());
                $message->setSentiment($sentiment);

                $entityManager->persist($message);
                $entityManager->flush();
                $this->addFlash('success', 'Reply sent.');
            } else {
                $this->addFlash('error', 'Message could not be sent. Please check files and content.');
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

        if ($ticket->getStatut() !== 'CLOSED') {
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
                $feedback->setRating((int) $rating);
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
        if (!$this->isCsrfTokenValid('close_ticket_' . $ticket->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
        }

        $ticket->setStatut('CLOSED');
        $ticket->setDateResolution(new \DateTime());
        $entityManager->flush();
        $this->addFlash('success', 'Ticket closed.');

        return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function editTicket(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertTicketOwnership($ticket);

        if (\in_array($ticket->getStatut(), ['RESOLVED', 'CLOSED'], true)) {
            $this->addFlash('warning', 'Resolved or closed tickets cannot be edited.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ticket updated.');

            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
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
        if (!$this->isCsrfTokenValid('delete_ticket_' . $ticket->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('support_index');
        }

        $entityManager->remove($ticket);
        $entityManager->flush();
        $this->addFlash('success', 'Ticket deleted.');

        return $this->redirectToRoute('support_index');
    }

    #[Route('/{id}/pdf', name: 'download_pdf', methods: ['GET'])]
    public function downloadPdf(Ticket $ticket): Response
    {
        $this->assertTicketOwnership($ticket);
        
        if ($ticket->getStatut() !== 'CLOSED') {
            $this->addFlash('error', 'PDF is only available for closed tickets.');
            return $this->redirectToRoute('support_show', ['id' => $ticket->getId()]);
        }

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        $html = $this->renderView('support/client/pdf_export.html.twig', [
            'ticket' => $ticket,
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="Ticket_' . $ticket->getId() . '.pdf"');

        return $response;
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

        if ($message->getUtilisateurId() !== $this->resolveUserId()) {
            throw $this->createAccessDeniedException('You can only edit your own messages.');
        }

        $form = $this->createForm(MessageTicketType::class, $message, ['is_admin' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('attachmentFiles')->getData();
            if ($files) {
                $filenames = $this->handleFileUploads($files);
                $message->setAttachmentsJson(json_encode($filenames));
            }
            $entityManager->flush();
            $this->addFlash('success', 'Message updated.');

            return $this->redirectToRoute('support_show', ['id' => $ticketId]);
        } elseif ($request->isMethod('POST')) {
            $post = $request->request->all();
            $contenu = null;
            $attachmentsJson = null;

            foreach ($post as $val) {
                if (\is_array($val)) {
                    $contenu = $val['contenu'] ?? $contenu;
                    $attachmentsJson = $val['attachmentsJson'] ?? $attachmentsJson;
                }
            }
            $contenu ??= ($post['contenu'] ?? null);
            $attachmentsJson ??= ($post['attachmentsJson'] ?? null);

            if ($contenu !== null && trim($contenu) !== '') {
                $message->setContenu(trim($contenu));
                if ($attachmentsJson !== null) {
                    $message->setAttachmentsJson(trim($attachmentsJson));
                }
                $entityManager->flush();
                $this->addFlash('success', 'Message updated.');
                return $this->redirectToRoute('support_show', ['id' => $ticketId]);
            }
            
            $this->addFlash('error', 'Update failed: Message cannot be empty.');
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

        if ($message->getUtilisateurId() !== $this->resolveUserId()) {
            throw $this->createAccessDeniedException('You can only delete your own messages.');
        }

        if (!$this->isCsrfTokenValid('delete_message_' . $messageId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid delete token.');

            return $this->redirectToRoute('support_show', ['id' => $ticketId]);
        }

        $entityManager->remove($message);
        $entityManager->flush();
        $this->addFlash('success', 'Message deleted.');

        return $this->redirectToRoute('support_show', ['id' => $ticketId]);
    }

    private function handleFileUploads(array $files): array
    {
        $filenames = [];
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/tickets';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($files as $file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            try {
                $file->move($uploadDir, $newFilename);
                $filenames[] = $newFilename;
            } catch (\Exception $e) {
                // Silently skip or log
            }
        }

        return $filenames;
    }

    private function assertTicketOwnership(Ticket $ticket): void
    {
        // Ownership checks intentionally disabled for demo/no-auth mode.
    }

    private function resolveUserId(): int
    {
        // Hardcoded demo mapping without auth/security.
        return 2;
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
            ->setUtilisateurId($this->resolveUserId())
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

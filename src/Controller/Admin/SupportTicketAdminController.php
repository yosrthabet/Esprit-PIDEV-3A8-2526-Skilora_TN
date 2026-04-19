<?php

namespace App\Controller\Admin;

use App\Entity\MessageTicket;
use App\Entity\Ticket;
use App\Form\MessageTicketType;
use App\Form\TicketAdminType;
use App\Repository\FeedbackRepository;
use App\Repository\MessageTicketRepository;
use App\Repository\TicketRepository;
use App\Service\GeminiService;
use App\Service\PublicTranslationService;
use App\Service\SupportNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/support', name: 'admin_support_')]
class SupportTicketAdminController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private SupportNotificationService $notifier
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TicketRepository $ticketRepository, FeedbackRepository $feedbackRepository): Response
    {
        $query = $request->query->get('q', '');
        $status = $request->query->get('status', 'all');
        $sortBy = $request->query->get('sortBy', 'latest');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 5;

        $dbStatus = $status === 'all' ? null : mb_strtoupper(str_replace('-', '_', $status));

        $tickets = $ticketRepository->search($query, $page, $limit, $dbStatus, $sortBy);
        $totalItems = $ticketRepository->countTotal($query, $dbStatus);
        $totalPages = max(1, ceil($totalItems / $limit));

        if ($request->isXmlHttpRequest()) {
            return $this->render('support/admin/_ticket_table.html.twig', [
                'tickets' => $tickets,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'search_query' => $query,
                'current_status' => $status,
                'current_sort' => $sortBy,
            ]);
        }

        return $this->render('support/admin/index.html.twig', [
            'tickets' => $tickets,
            'status_stats' => $ticketRepository->countByStatus(),
            'priority_stats' => $ticketRepository->countByPriority(),
            'category_stats' => $ticketRepository->countByCategory(),
            'daily_stats' => $ticketRepository->countLast7DaysVolume(),
            'avg_rating' => $feedbackRepository->getAverageRating(),
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search_query' => $query,
            'current_status' => $status,
            'current_sort' => $sortBy,
        ]);
    }

    #[Route('/calendar', name: 'calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        return $this->render('support/admin/calendar.html.twig');
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
                str_replace('"', '""', $ticket->getCategory() ?? ''),
                $ticket->getPriority(),
                $ticket->getStatus(),
                $ticket->getCreatedDate()?->format('Y-m-d H:i') ?? '-'
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
        $oldStatus = $ticket->getStatus();
        $adminForm = $this->createForm(TicketAdminType::class, $ticket);
        $adminForm->handleRequest($request);

        if ($adminForm->isSubmitted() && $adminForm->isValid()) {
            $newStatus = $ticket->getStatus();
            if ($oldStatus !== $newStatus) {
                $this->notifier->notifyStatusChange($ticket, $oldStatus, $newStatus);
            }

            if (\in_array($newStatus, ['RESOLVED', 'CLOSED'], true) && !$ticket->getResolvedDate()) {
                $ticket->setResolvedDate(new \DateTime());
            }
            if (!\in_array($newStatus, ['RESOLVED', 'CLOSED'], true)) {
                $ticket->setResolvedDate(null);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Ticket updated and notification sent.');

            return $this->redirectToRoute('admin_support_show', ['id' => $ticket->getId()]);
        }

        return $this->renderShowPage($ticket, $request, $entityManager, $messageRepository);
    }

    #[Route('/{id}/messages', name: 'message_create', methods: ['POST'])]
    public function createMessage(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository,
        GeminiService $gemini
    ): Response {
        $message = (new MessageTicket())
            ->setTicket($ticket)
            ->setSenderId($this->resolveAdminId())
            ->setIsInternal(false)
            ->setCreatedDate(new \DateTime());

        $form = $this->createForm(MessageTicketType::class, $message, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('attachmentFiles')->getData();
            if ($files) {
                $filenames = $this->handleFileUploads($files);
                $message->setAttachmentsJson(json_encode($filenames));
            }

            $sentiment = $gemini->detectTone($message->getMessage());
            $message->setSentiment($sentiment);

            $entityManager->persist($message);
            $entityManager->flush();
            $this->addFlash('success', 'Message posted with emotional analysis.');
        } else {
            $this->addFlash('error', 'Message not sent. Please check fields.');
        }

        return $this->renderShowPage($ticket, $request, $entityManager, $messageRepository);
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
            }
        }

        return $filenames;
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
                    $contenu = $val['message'] ?? $contenu;
                    $attachmentsJson = $val['attachmentsJson'] ?? $attachmentsJson;
                    $isInternal = $val['isInternal'] ?? $isInternal;
                }
            }
            $contenu ??= ($post['message'] ?? null);
            $attachmentsJson ??= ($post['attachmentsJson'] ?? null);
            $isInternal ??= ($post['isInternal'] ?? null);

            if ($contenu !== null && trim($contenu) !== '') {
                $message->setMessage(trim($contenu));
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

    #[Route('/messages/{id}/translate', name: 'message_translate', methods: ['GET'])]
    public function translateMessage(
        MessageTicket $message,
        Request $request,
        PublicTranslationService $translator
    ): JsonResponse {
        $text = $message->getMessage();
        if (!$text) {
            return new JsonResponse(['error' => 'Message is empty'], 400);
        }

        $targetLang = $request->query->get('target', 'en');
        $translated = $translator->translate($text, $targetLang);

        if (!$translated) {
            return new JsonResponse([
                'error' => 'Public translation service failed. Try again later.',
                'originalText' => $text
            ], 500);
        }

        return new JsonResponse(['translatedText' => $translated]);
    }

    #[Route('/{id}/pdf', name: 'download_pdf', methods: ['GET'])]
    public function downloadPdf(Ticket $ticket): Response
    {
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        $html = $this->renderView('support/admin/pdf_export.html.twig', [
            'ticket' => $ticket,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="Admin_Ticket_' . $ticket->getId() . '.pdf"');

        return $response;
    }

    #[Route('/messages/{id}/analyze-mood', name: 'message_analyze_mood', methods: ['POST'])]
    public function analyzeMood(
        MessageTicket $message,
        GeminiService $gemini,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $mood = $gemini->detectTone($message->getMessage());

        if ($mood) {
            $message->setSentiment($mood);
            $entityManager->flush();
            return new JsonResponse(['mood' => $mood]);
        }

        return new JsonResponse(['error' => 'AI Analysis failed'], 500);
    }

    #[Route('/translate-text', name: 'translate_text', methods: ['GET'])]
    public function translateText(
        Request $request,
        PublicTranslationService $translator
    ): JsonResponse {
        $text = $request->query->get('text');
        $targetLang = $request->query->get('target', 'en');

        if (!$text) {
            return new JsonResponse(['error' => 'No text provided'], 400);
        }

        $translated = $translator->translate($text, $targetLang);

        return new JsonResponse(['translated' => $translated]);
    }

    #[Route('/{id}/translate', name: 'ticket_translate', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function translateTicket(
        Ticket $ticket,
        Request $request,
        PublicTranslationService $translator
    ): JsonResponse {
        $targetLang = $request->query->get('target', 'en');

        $translatedSubject = $translator->translate($ticket->getSubject(), $targetLang);
        $translatedDescription = $translator->translate($ticket->getDescription(), $targetLang);

        return new JsonResponse([
            'subject' => $translatedSubject,
            'description' => $translatedDescription
        ]);
    }

    private function resolveAdminId(): int
    {
        return 1;
    }

    private function renderShowPage(
        Ticket $ticket,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageTicketRepository $messageRepository
    ): Response {
        $adminForm = $this->createForm(TicketAdminType::class, $ticket);

        $newMessage = (new MessageTicket())
            ->setTicket($ticket)
            ->setSenderId($this->resolveAdminId())
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

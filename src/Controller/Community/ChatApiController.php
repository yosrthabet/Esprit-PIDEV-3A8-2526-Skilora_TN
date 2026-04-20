<?php

namespace App\Controller\Community;

use App\Entity\DmConversation;
use App\Entity\DmMessage;
use App\Entity\User;
use App\Repository\DmConversationRepository;
use App\Repository\DmMessageRepository;
use App\Repository\MemberInvitationRepository;
use App\Service\AISummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/community/reseau/api')]
#[IsGranted('ROLE_USER')]
class ChatApiController extends AbstractController
{
    public function __construct(private HubInterface $hub)
    {
    }

    private function safePublish(Update $update): void
    {
        try {
            $this->hub->publish($update);
        } catch (\Throwable) {
            // Mercure hub not available — skip real-time push
        }
    }

    /**
     * Send a text message via AJAX.
     */
    #[Route('/message', name: 'app_chat_api_send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        EntityManagerInterface $em,
        DmConversationRepository $conversationRepository,
        MemberInvitationRepository $invitationRepository,
    ): JsonResponse {
        $me = $this->requireUser();
        $data = json_decode($request->getContent(), true);

        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $body = trim((string) ($data['body'] ?? ''));

        if (!$conversationId || !$body) {
            return $this->json(['error' => 'Paramètres manquants.'], 400);
        }

        if (mb_strlen($body) > 4000) {
            return $this->json(['error' => 'Message trop long (max 4000 caractères).'], 400);
        }

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $other = $conversation->otherParticipant($me);
        if (!$invitationRepository->areFriends($me, $other)) {
            return $this->json(['error' => 'Amis uniquement.'], 403);
        }

        $message = new DmMessage();
        $message->setConversation($conversation);
        $message->setSender($me);
        $message->setBody($body);
        $message->setMessageType(DmMessage::TYPE_TEXT);
        $em->persist($message);
        $em->flush();

        $msgPayload = [
            'id' => $message->getId(),
            'body' => $message->getBody(),
            'sender_id' => $me->getId(),
            'sender_name' => $me->getFullName() ?? $me->getUsername(),
            'sender_initials' => $this->getInitials($me),
            'message_type' => 'text',
            'created_at' => $message->getCreatedAt()->format('H:i'),
        ];

        // Publish to Mercure for real-time delivery
        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/messages',
            json_encode(['type' => 'new_message', 'message' => $msgPayload])
        ));

        return $this->json(array_merge($msgPayload, ['is_mine' => true]));
    }

    /**
     * Upload and send a voice message.
     */
    #[Route('/voice', name: 'app_chat_api_voice', methods: ['POST'])]
    public function sendVoice(
        Request $request,
        EntityManagerInterface $em,
        DmConversationRepository $conversationRepository,
        MemberInvitationRepository $invitationRepository,
        SluggerInterface $slugger,
    ): JsonResponse {
        $me = $this->requireUser();

        $conversationId = (int) $request->request->get('conversation_id', 0);
        /** @var UploadedFile|null $voiceFile */
        $voiceFile = $request->files->get('voice');

        if (!$conversationId || !$voiceFile) {
            return $this->json(['error' => 'Paramètres manquants.'], 400);
        }

        // Max 10 MB for voice
        if ($voiceFile->getSize() > 10 * 1024 * 1024) {
            return $this->json(['error' => 'Fichier audio trop volumineux (max 10 Mo).'], 400);
        }

        $allowedMimes = ['audio/webm', 'audio/ogg', 'audio/mp4', 'audio/mpeg', 'audio/wav', 'video/webm'];
        if (!in_array($voiceFile->getMimeType(), $allowedMimes, true)) {
            return $this->json(['error' => 'Format audio non supporté.'], 400);
        }

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $other = $conversation->otherParticipant($me);
        if (!$invitationRepository->areFriends($me, $other)) {
            return $this->json(['error' => 'Amis uniquement.'], 403);
        }

        // Save file
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/voice';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeFilename = $slugger->slug('voice-' . $me->getId() . '-' . time());
        $extension = $voiceFile->guessExtension() ?: 'webm';
        $newFilename = $safeFilename . '.' . $extension;
        $voiceFile->move($uploadDir, $newFilename);

        $voiceUrl = '/uploads/voice/' . $newFilename;

        $message = new DmMessage();
        $message->setConversation($conversation);
        $message->setSender($me);
        $message->setBody('🎤 Message vocal');
        $message->setMessageType(DmMessage::TYPE_VOICE);
        $message->setVoiceUrl($voiceUrl);
        $em->persist($message);
        $em->flush();

        $msgPayload = [
            'id' => $message->getId(),
            'body' => $message->getBody(),
            'voice_url' => $voiceUrl,
            'sender_id' => $me->getId(),
            'sender_name' => $me->getFullName() ?? $me->getUsername(),
            'sender_initials' => $this->getInitials($me),
            'message_type' => 'voice',
            'created_at' => $message->getCreatedAt()->format('H:i'),
        ];

        // Publish to Mercure for real-time delivery
        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/messages',
            json_encode(['type' => 'new_message', 'message' => $msgPayload])
        ));

        return $this->json(array_merge($msgPayload, ['is_mine' => true]));
    }

    /**
     * Upload and send an image message.
     */
    #[Route('/image', name: 'app_chat_api_image', methods: ['POST'])]
    public function sendImage(
        Request $request,
        EntityManagerInterface $em,
        DmConversationRepository $conversationRepository,
        MemberInvitationRepository $invitationRepository,
        SluggerInterface $slugger,
    ): JsonResponse {
        $me = $this->requireUser();

        $conversationId = (int) $request->request->get('conversation_id', 0);
        /** @var UploadedFile|null $imageFile */
        $imageFile = $request->files->get('image');

        if (!$conversationId || !$imageFile) {
            return $this->json(['error' => 'Paramètres manquants.'], 400);
        }

        // Max 5 MB for images
        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Image trop volumineuse (max 5 Mo).'], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimes, true)) {
            return $this->json(['error' => 'Format d\'image non supporté (JPEG, PNG, GIF, WebP).'], 400);
        }

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $other = $conversation->otherParticipant($me);
        if (!$invitationRepository->areFriends($me, $other)) {
            return $this->json(['error' => 'Amis uniquement.'], 403);
        }

        // Save file
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/chat_images';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeFilename = $slugger->slug('img-' . $me->getId() . '-' . time());
        $extension = $imageFile->guessExtension() ?: 'jpg';
        $newFilename = $safeFilename . '.' . $extension;
        $imageFile->move($uploadDir, $newFilename);

        $imageUrl = '/uploads/chat_images/' . $newFilename;

        $message = new DmMessage();
        $message->setConversation($conversation);
        $message->setSender($me);
        $message->setBody('📷 Photo');
        $message->setMessageType(DmMessage::TYPE_IMAGE);
        $message->setImageUrl($imageUrl);
        $em->persist($message);
        $em->flush();

        $msgPayload = [
            'id' => $message->getId(),
            'body' => $message->getBody(),
            'image_url' => $imageUrl,
            'sender_id' => $me->getId(),
            'sender_name' => $me->getFullName() ?? $me->getUsername(),
            'sender_initials' => $this->getInitials($me),
            'message_type' => 'image',
            'created_at' => $message->getCreatedAt()->format('H:i'),
        ];

        // Publish to Mercure for real-time delivery
        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/messages',
            json_encode(['type' => 'new_message', 'message' => $msgPayload])
        ));

        return $this->json(array_merge($msgPayload, ['is_mine' => true]));
    }

    /**
     * Notify that current user is typing (published via Mercure).
     */
    #[Route('/typing', name: 'app_chat_api_typing', methods: ['POST'])]
    public function typing(
        Request $request,
        DmConversationRepository $conversationRepository,
    ): JsonResponse {
        $me = $this->requireUser();
        $data = json_decode($request->getContent(), true);
        $conversationId = (int) ($data['conversation_id'] ?? 0);

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        // Publish typing event via Mercure
        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/typing',
            json_encode([
                'type' => 'typing',
                'user_id' => $me->getId(),
                'user_name' => $me->getFullName() ?? $me->getUsername(),
                'timestamp' => time(),
            ])
        ));

        return $this->json(['ok' => true]);
    }

    /**
     * Mark all messages in a conversation as read by the current user.
     */
    #[Route('/read/{conversationId}', name: 'app_chat_api_read', methods: ['POST'])]
    public function markAsRead(
        int $conversationId,
        DmConversationRepository $conversationRepository,
        DmMessageRepository $messageRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $me = $this->requireUser();

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        // Get IDs of unread messages before updating
        $qbIds = $em->createQueryBuilder();
        $qbIds->select('m.id')
            ->from(DmMessage::class, 'm')
            ->where('m.conversation = :conv')
            ->andWhere('m.sender != :me')
            ->andWhere('m.isRead = :false')
            ->setParameter('conv', $conversation)
            ->setParameter('me', $me)
            ->setParameter('false', false);
        $readMessageIds = array_column($qbIds->getQuery()->getArrayResult(), 'id');

        // Mark unread messages from the OTHER user as read
        $qb = $em->createQueryBuilder();
        $qb->update(DmMessage::class, 'm')
            ->set('m.isRead', ':true')
            ->set('m.readAt', ':now')
            ->where('m.conversation = :conv')
            ->andWhere('m.sender != :me')
            ->andWhere('m.isRead = :false')
            ->setParameter('conv', $conversation)
            ->setParameter('me', $me)
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->setParameter('now', new \DateTimeImmutable());
        $updated = $qb->getQuery()->execute();

        // Publish read receipt via Mercure so the sender sees ✓✓ instantly
        if ($updated > 0) {
            $this->safePublish(new Update(
                'chat/conversation/' . $conversationId . '/read',
                json_encode([
                    'type' => 'read_receipt',
                    'reader_id' => $me->getId(),
                    'message_ids' => $readMessageIds,
                    'read_at' => (new \DateTimeImmutable())->format('H:i'),
                ])
            ));
        }

        return $this->json(['marked' => $updated]);
    }

    /**
     * Publish presence (online/offline) for a conversation.
     */
    #[Route('/presence', name: 'app_chat_api_presence', methods: ['POST'])]
    public function presence(
        Request $request,
        DmConversationRepository $conversationRepository,
    ): JsonResponse {
        $me = $this->requireUser();
        $data = json_decode($request->getContent(), true);
        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $status = ($data['status'] ?? '') === 'online' ? 'online' : 'offline';

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/presence',
            json_encode([
                'type' => 'presence',
                'user_id' => $me->getId(),
                'status' => $status,
            ])
        ));

        return $this->json(['ok' => true]);
    }

    /**
     * Poll for new messages since a given message ID.
     */
    #[Route('/messages/{conversationId}', name: 'app_chat_api_poll', methods: ['GET'])]
    public function pollMessages(
        int $conversationId,
        Request $request,
        DmConversationRepository $conversationRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $me = $this->requireUser();

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $afterId = $request->query->getInt('after', 0);

        $qb = $em->createQueryBuilder();
        $qb->select('m')
            ->from(DmMessage::class, 'm')
            ->where('m.conversation = :conv')
            ->setParameter('conv', $conversation)
            ->orderBy('m.id', 'ASC');

        if ($afterId > 0) {
            $qb->andWhere('m.id > :after')
                ->setParameter('after', $afterId);
        }

        $messages = $qb->getQuery()->getResult();

        $result = [];
        foreach ($messages as $m) {
            /** @var DmMessage $m */
            $isMine = $m->getSender()->getId() === $me->getId();
            $result[] = [
                'id' => $m->getId(),
                'body' => $m->getBody(),
                'sender_id' => $m->getSender()->getId(),
                'sender_name' => $m->getSender()->getFullName() ?? $m->getSender()->getUsername(),
                'sender_initials' => $this->getInitials($m->getSender()),
                'message_type' => $m->getMessageType(),
                'voice_url' => $m->getVoiceUrl(),
                'image_url' => $m->getImageUrl(),
                'created_at' => $m->getCreatedAt()->format('H:i'),
                'is_mine' => $isMine,
                'is_read' => $m->isRead(),
                'read_at' => $m->getReadAt()?->format('H:i'),
            ];
        }

        // Also gather read status for my messages (to show checkmarks)
        $myReadStatus = [];
        $qbRead = $em->createQueryBuilder();
        $qbRead->select('m.id, m.isRead')
            ->from(DmMessage::class, 'm')
            ->where('m.conversation = :conv')
            ->andWhere('m.sender = :me')
            ->setParameter('conv', $conversation)
            ->setParameter('me', $me);
        foreach ($qbRead->getQuery()->getArrayResult() as $row) {
            $myReadStatus[$row['id']] = $row['isRead'];
        }

        return $this->json([
            'messages' => $result,
            'read_status' => $myReadStatus,
        ]);
    }

    /**
     * Summarize conversation messages using AI.
     */
    #[Route('/summarize/{conversationId}', name: 'app_chat_api_summarize', methods: ['POST'])]
    public function summarize(
        int $conversationId,
        DmConversationRepository $conversationRepository,
        EntityManagerInterface $em,
        AISummaryService $aiService,
    ): JsonResponse {
        $me = $this->requireUser();

        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        // Get last 50 text messages from conversation
        $qb = $em->createQueryBuilder();
        $qb->select('m')
            ->from(DmMessage::class, 'm')
            ->where('m.conversation = :conv')
            ->andWhere('m.messageType = :textType')
            ->setParameter('conv', $conversation)
            ->setParameter('textType', DmMessage::TYPE_TEXT)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(50);

        $messages = array_reverse($qb->getQuery()->getResult());

        if (count($messages) < 2) {
            return $this->json(['error' => 'Pas assez de messages à résumer (minimum 2).'], 400);
        }

        $formatted = [];
        foreach ($messages as $m) {
            $sender = $m->getSender()->getFullName() ?? $m->getSender()->getUsername();
            $formatted[] = $sender . ': ' . $m->getBody();
        }

        $summary = $aiService->summarizeDiscussion($formatted, 'fr');

        return $this->json([
            'summary' => $summary ?: 'Aucun résumé disponible.',
            'message_count' => count($messages),
        ]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function getInitials(User $user): string
    {
        $name = $user->getFullName() ?? $user->getUsername();
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $w) {
            if ($w !== '') {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1));
            }
        }

        return mb_substr($initials, 0, 2);
    }
}
